<?php
require('postmark.php');
require('diff_settings.php');

//setup database
try {
        $dbh = new PDO("mysql:host=$dbhost;dbname=$dbname" , "$dbuser", "$dbpass");
    }
catch(PDOException $e)
    {
        echo $e->getMessage();
    }

//Remove first 44 lines from the docket; reduce false positives
function excerpt($id, $path) {
    $file = $path . $id . ".dk";
    $lines = file($file);
    $excerpt = implode('', array_slice($lines,44)); 
    $fp = fopen($file, "w");
    fwrite($fp,$excerpt);
    fclose($fp);

    $tmp_file = $path . $id . "_tmp";
    $lines = file($tmp_file);
    $excerpt = implode('', array_slice($lines,44)); 
    $fp = fopen($tmp_file, "w");
    fwrite($fp,$excerpt);
    fclose($fp);
}

//set some variables for logging
$cases_checked = 0;
$changes_detected = 0;
$errors = 0;
$time_start = microtime(true);

//loop throught the cases table
$q = $dbh->prepare("select * from docketminder_cases");
$q->execute();
$result = $q->fetchAll();
foreach ($result as $r) {
    // $file is our stored version of the docket master
    // $temp_file is file we get now to check for changes
    $file = "$path_to_files" . $r['id'] . ".dk";
    $temp_file = "$path_to_files" . $r['id'] . "_tmp";

    //File should be created at sign up, but if for some reason
    //not, do it now.
    if(!file_exists($file))
    {
        $ch = curl_init($r['url']);
        $fp = fopen($file, "w");
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_exec($ch);
        if(curl_errno($ch)){
            $errors++;
            continue; //just give up on this one for today, go to next case
        }
        curl_close($ch);
        fclose($fp);
    }
    else //get the new file and do the diff
    {
        $ch = curl_init($r['url']);

        $fp = fopen($temp_file, "w");
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_exec($ch);
        if(curl_errno($ch)){
            $errors++;
            $cases_checked++;
            continue; //give up on this one for today, go to next case
        }
        curl_close($ch);
        fclose($fp);

        //Remove first 44 lines from tmp file; already removed from the base copy
        $tmp_lines = file($temp_file);
        $excerpt = implode('', array_slice($tmp_lines,44)); 
        $fp = fopen($temp_file, "w");
        fwrite($fp,$excerpt);
        fclose($fp);

        //Create an array of diffed lines
        $lines = array();
        exec("diff $file $temp_file",$lines); 

        if (count($lines) > 0)
        {
            //now take off the cruft and output a string
            foreach ($lines as &$line) {
                $line = trim($line,">");
            }

            $diff = implode("\n",(array_slice($lines,1)));

            //notify user - use postmark app
            $user = $dbh->prepare('select email from docketminder_users where email = ?');
            $user->bindParam(1,$r['tracked_by']);
            $user->execute();
            $u = $user->fetch();
            $case_name = ucwords(strtolower($r['name']));
            $subject = "DocketMinder Update: $case_name";
            $message = "DocketMinder has detected an update to the $case_name docket.\n\n"
            . $diff . "\n\nTo view this docket: " . $r['url'] . "\n\nTo change your DockeMinder settings: http://loyolalawtech.org/docketminder";

            $postmark = new Postmark("$postmark_key","$postmark_email");
            $mail = $postmark->to($u['email'])
            ->subject($subject)
            ->plain_message($message)
            ->send();
            //update db (tracked date and change date) 
            $update = $dbh->prepare('UPDATE docketminder_cases SET last_tracked = NOW(),last_changed = NOW()  WHERE id = ?');
            $changes_detected++;
        }
        else
        {
            //update db (only track date) 
            $update = $dbh->prepare('UPDATE docketminder_cases SET last_tracked = NOW() WHERE id = ?');
        }

        //overwrite the old file with new file
        rename($temp_file, $file);

        $update->bindParam(1,$r['id']);
        $update->execute();
        $cases_checked++;
    }

    //Find execution time
    $time_end = microtime(true);
    $execution_time = ($time_end - $time_start)/60;

    //Write log file
    $fp = fopen('log', "a");
    $date = date('n/j/Y g:i A');
    $message =  "Diff finished on $date in " . round($execution_time,2) . " minutes.  $cases_checked cases checked, $changes_detected changes detected, $errors errors\n";
    fwrite($fp,$message);
    fclose($fp);
}
