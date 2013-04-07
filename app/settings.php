<?php

class Settings {

    function beforeRoute($f3){

        if (!$f3->get('SESSION.isLoggedIn')) {

            $f3->error(401);
        }
        $f3->set('message','');
        $f3->set('error','');
    }

    function index($f3) {

        $db = $f3->get('DB');
        $user_data = new DB\SQL\Mapper($db,'docketminder_users');
        $user_data->load(array('email=?',$f3->get('SESSION.email')));
        $user_data->copyTo('USER');
        $f3->set('content','settings.html');
        $f3->set('header','header.html');
        $f3->set('title','Docketminder - Your Settings');
        echo Template::instance()->render('main_settings.html');
    }

    function change_user_data($f3) {
        $db = $f3->get('DB');
        $user_data = new DB\SQL\Mapper($db,'docketminder_users');
        $user_data->load(array('email=?',$f3->get('SESSION.email')));
        $user_data->name = $f3->get('POST.name');
        $user_data->email = $f3->get('POST.email');
        $user_data->save();
        //update all records
        $db->exec('update docketminder_cases set tracked_by = :new where tracked_by = :old',array(':old' => $f3->get('SESSION.email'),':new' => $f3->get('POST.email')));
        $f3->set('SESSION.email', $user_data->email);
        $f3->set('SESSION.name', $user_data->name);
        $f3->set('message','Your data successfully updated.');
        $this->index($f3);
    }
    
    function change_password($f3){
        $db = $f3->get('DB');
        $user_data = new DB\SQL\Mapper($db,'docketminder_users');
        $user_data->load(array('email=?',$f3->get('SESSION.email')));
        $old_password = $f3->get('POST.password');
        $new_password = $f3->get('POST.new_password');
        if ($user_data->password === md5($old_password)){
            $user_data->password = md5($new_password);
            $user_data->save();
            $f3->set('message','<strong>Success!</strong> Your password successfully changed.');
            $this->index($f3);
        }
        else {
            $f3->set('error','<strong>Oops!</strong> Your old password was incorrect.');
            $this->index($f3);

        }


    }
}
