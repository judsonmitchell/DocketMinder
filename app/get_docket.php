<?php
//Script to fetch base copy of the docket master

if ($argv[5] = '1'){
    $url = "http://www.opcso.org/dcktmstr/555555.php?&domagn=" . $argv[2];
} else {
    $url = "http://www.opcso.org/dcktmstr/666666.php?&docase=" . $argv[2];
}
$ch = curl_init($url);
$fp = fopen($argv[4] . '/' . $argv[3] . '.dk', "w");
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 300);
curl_exec($ch);
curl_close($ch);
fclose($fp);

//Now chop first 44 lines
$file = $argv[4] . '/' . $argv[3] . '.dk';
$lines = file($file);
$excerpt = implode('', array_slice($lines,44)); 
$fp = fopen($file, "w");
fwrite($fp,$excerpt);
fclose($fp);
