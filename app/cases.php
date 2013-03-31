<?php

class Cases {

    function all($f3) {
        if ($f3->get('SESSION.isLoggedIn'))
        {
            $db = $f3->get('DB');
            $f3->set('cases',new DB\SQL\Mapper($db,'docketminder_cases'));
            $f3->set('CASES',$f3->get('cases')->find(array('tracked_by=?',$f3->get('SESSION.email'))));
            $f3->set('content','cases.html');
            $f3->set('header','header.html');
            $f3->set('title','Docketminder - Your Cases');
            echo Template::instance()->render('main.html');
        }
        else
        {
            $this->error($f3);
        }
    }

    function add($f3) {

        if ($f3->get('SESSION.isLoggedIn'))
        {
            //check if curl is installed
            $ch = curl_init($url);
            if (!$ch) {
                die('Sorry, curl is not installed on your server');
            }
            //Add case data to db
            $db = $f3->get('DB');
            $cases = new DB\SQL\Mapper($db,'docketminder_cases');
            $cases->copyFrom('POST');
            $url = 'http://www.opcso.org/dcktmstr/666666.php?&docase=' . $cases->number;
            $cases->url = $url;
            $cases->tracked_by = $f3->get('SESSION.email');
            $cases->date_tracked = date('Y-m-d H:i:s');
            $cases->save();
            echo "Case Added";

            //Get a base copy of the docket master for later comparison
            //do this instead http://stackoverflow.com/a/124557/49359
            //pass the arguments this way: http://stackoverflow.com/a/6779804/49359
            $fp = fopen('app/files/' . $cases->_id, "w");
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);

 
        }
        else
        {
            $this->error($f3);
        }
    }

    function delete($f3,$params) {

        if ($f3->get('SESSION.isLoggedIn'))
        {
            $db = $f3->get('DB');
            $cases = new DB\SQL\Mapper($db,'docketminder_cases');
            $cases->load(array('id=?',$params['id']));
            $cases->erase(); 
            echo "Case Deleted";
        }
        else
        {
            $this->error($f3);
        }
    }

    function refresh_table($f3){
        if ($f3->get('SESSION.isLoggedIn'))
        {
            $db = $f3->get('DB');
            $f3->set('cases',new DB\SQL\Mapper($db,'docketminder_cases'));
            $f3->set('CASES',$f3->get('cases')->find(array('tracked_by=?',$f3->get('SESSION.email'))));
            echo Template::instance()->render('case_table_partial.html');
        }
        else
        {

            $this->error($f3);
        }

    }

    function error($f3){
        $f3->set('content','error.html');
        $f3->set('title','Error');
        echo Template::instance()->render('main.html');
    }

}
