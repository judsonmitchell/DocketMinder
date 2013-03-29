<?php

class Cases {

    function all($f3) {
        if ($f3->get('SESSION.isLoggedIn'))
        {
            $db = $f3->get('DB');
            $f3->set('cases',new DB\SQL\Mapper($db,'cases'));
            $f3->set('CASES',$f3->get('cases')->find(array('tracked_by=?',$f3->get('SESSION.username'))));
            $f3->set('content','cases.html');
            $f3->set('title','Your Cases');
            echo Template::instance()->render('main.html');
        }
        else
        {
            $this->error();
        }
    }

    function add($f3) {

        if ($f3->get('SESSION.isLoggedIn'))
        {
            //Add case data to db
            $db = $f3->get('DB');
            $cases = new DB\SQL\Mapper($db,'cases');
            $cases->copyFrom('POST');
            $url = 'http://www.opcso.org/dcktmstr/666666.php?&docase=' . $cases->number;
            $cases->url = $url;
            $cases->tracked_by = $f3->get('SESSION.username');
            $cases->date_tracked = date('Y-m-d H:i:s');
            $cases->save();
            echo "Case Added";

            //Get a base copy of the docket master for later comparison
            //do this instead http://stackoverflow.com/a/124557/49359
            //pass the arguments this way: http://stackoverflow.com/a/6779804/49359
            $ch = curl_init($url);
            $fp = fopen('app/files/' . $cases->_id, "w");
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);

 
        }
        else
        {
            $this->error();
        }
    }

    function delete($f3,$params) {

        if ($f3->get('SESSION.isLoggedIn'))
        {
            $db = $f3->get('DB');
            $cases = new DB\SQL\Mapper($db,'cases');
            $cases->load(array('id=?',$params['id']));
            $cases->erase(); 
            echo "Case Deleted";
        }
        else
        {
            $this->error();
        }
    }

    function refresh_table($f3){
        if ($f3->get('SESSION.isLoggedIn'))
        {
            $db = $f3->get('DB');
            $f3->set('cases',new DB\SQL\Mapper($db,'cases'));
            $f3->set('CASES',$f3->get('cases')->find(array('tracked_by=?',$f3->get('SESSION.username'))));
            echo Template::instance()->render('case_table_partial.html');
        }
        else
        {

            $this->error();
        }

    }

    function error($f3){
        $f3->set('content','error.html');
        $f3->set('title','Error');
        echo Template::instance()->render('main.html');
    }

}
