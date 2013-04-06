<?php

class Settings {

    function beforeRoute($f3){

        if (!$f3->get('SESSION.isLoggedIn')) {

            $f3->error(401);
        }
    }

    function index($f3) {

        $db = $f3->get('DB');
        $user_data = new DB\SQL\Mapper($db,'docketminder_users');
        $user_data->load(array('email=?',$f3->get('SESSION.email')));
        $user_data->copyTo('USER');
        $f3->set('content','settings.html');
        $f3->set('header','header.html');
        $f3->set('title','Docketminder - Your Settings');
        echo Template::instance()->render('main.html');
    }

    function change_user_data($f3) {
        $db = $f3->get('DB');
        $user_data = new DB\SQL\Mapper($db,'docketminder_users');
        $user_data->load(array('email=?',$f3->get('SESSION.email')));
        $user_data->name = $f3->get('POST.name');
        $user_data->email = $f3->get('POST.email');
        $user_data->save();
        $f3->set('SESSION.email', $user_data->email);
        $f3->set('SESSION.name', $user_data->name);
        $f3->reroute('/cases/all');
    }
    
    function change_password($f3){
        $db = $f3->get('DB');
        $user_data = new DB\SQL\Mapper($db,'docketminder_users');
        $user_data->load(array('email=?',$f3->get('SESSION.email')));
        $new_password = $f3->get('POST.password');
        if ($user_data->password === md5($new_password)){
            $user_data->password = md5($new_password);
            $user_data->save();
        }
        else {
            die('not ok');

        }


    }
}
