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
            $f3->error(401);
        }
    }

    function add($f3) {

        if ($f3->get('SESSION.isLoggedIn'))
        {
            //Add case data to db
            $db = $f3->get('DB');
            $cases = new DB\SQL\Mapper($db,'docketminder_cases');
            $cases->copyFrom('POST');
            $url = 'http://www.opcso.org/dcktmstr/666666.php?&docase=' . $cases->number;
            $cases->url = $url;
            $cases->tracked_by = $f3->get('SESSION.email');
            $cases->date_tracked = date('Y-m-d H:i:s');
            $cases->save();

            //Get a base copy of the docket master for later comparison
            //Run the script asynchronously: see http://stackoverflow.com/a/124557/49359
            //and http://stackoverflow.com/a/6779804/49359
            exec("php app/get_docket.php -- $cases->number $cases->_id " . $f3->get('path_to_files'). " > /dev/null &");

            $resp = array('status'=>'success','message'=>'<strong>Success!</strong> We are now tracking the case for you.');
            echo json_encode($resp);

        }
        else
        {
            $f3->error(401);
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
            $f3->error(401);
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

            $f3->error(401);
        }

    }
}
