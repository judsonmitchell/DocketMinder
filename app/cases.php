<?php

class Cases {

    function beforeRoute($f3){
        if (!$f3->get('SESSION.isLoggedIn')) {

            $f3->reroute('/users/login');
        }
    }

    function all($f3) {
        $db = $f3->get('DB');
        $f3->set('cases',new DB\SQL\Mapper($db,'docketminder_cases'));
        $f3->set('CASES',$f3->get('cases')->find(array('tracked_by=?',$f3->get('SESSION.email')),array('order'=>'id DESC')));
        $f3->set('content','cases.html');
        $f3->set('header','header.html');
        $f3->set('title','Docketminder - Your Cases');
        echo Template::instance()->render('main.html');
    }

    function add($f3) {
        //Add case data to db
        $db = $f3->get('DB');
        $cases = new DB\SQL\Mapper($db,'docketminder_cases');
        $cases->copyFrom('POST');
        $cn = $cases->number;
        if (substr($cn,0,1) == 'm' || substr($cn,0,1) == 'M'){
            $url = 'http://www.opcso.org/dcktmstr/555555.php?&domagn=' . preg_replace("/[^0-9,.]/", "", substr($cn,1));
            $mag_flag = '1';
            $cn_m = substr($cn,1);
        } else {
            $url = 'http://www.opcso.org/dcktmstr/666666.php?&docase=' . preg_replace("/[^0-9,.]/", "", $cn);
            $mag_flag = '0';
            $cn_m = $cn;
        }
        $cases->url = $url;
        $cases->tracked_by = $f3->get('SESSION.email');
        $cases->date_tracked = date('Y-m-d H:i:s');
        $cases->save();

        //Get a base copy of the docket master for later comparison
        //Run the script asynchronously: see http://stackoverflow.com/a/124557/49359
        //and http://stackoverflow.com/a/6779804/49359
        exec("php app/get_docket.php -- $cn_m $cases->_id " . $f3->get('path_to_files'). " $mag_flag  > /dev/null &");

        $resp = array('status'=>'success','message'=>'<strong>Success!</strong> We are now tracking the case for you.');
        echo json_encode($resp);
    }

    function delete($f3,$params) {
        $db = $f3->get('DB');
        $cases = new DB\SQL\Mapper($db,'docketminder_cases');
        $cases->load(array('id=?',$params['id']));
        $cases->erase(); 
        unlink($f3->get('path_to_files') . '/' . $params['id'] . '.dk');
        echo "<strong>Removed!</strong> No longer tracking case.";
    }

    function refresh_table($f3){
        $db = $f3->get('DB');
        $f3->set('cases',new DB\SQL\Mapper($db,'docketminder_cases'));
        $f3->set('CASES',$f3->get('cases')->find(array('tracked_by=?',$f3->get('SESSION.email')),array('order'=>'id DESC')));
        echo Template::instance()->render('case_table_partial.html');
    }
}
