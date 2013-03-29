<?php

class Users {

    function signup($f3) {

        $f3->mset(array('title'=>'New Account','content'=>'new_user.html','message'=>FALSE));
        echo Template::instance()->render('main.html');

    }

    function add($f3) {
        $db = $f3->get('DB');
        $f3->set('user',new DB\SQL\Mapper($db,'docketminder_users'));
        $f3->get('user')->copyFrom('POST');
        $f3->get('user')->password = md5($f3->get('POST.password'));
        $f3->get('user')->save();

    }

    function login($f3) {

        $f3->mset(array('title'=>'Login','content'=>'login.html','message'=>FALSE));
        echo Template::instance()->render('main.html');

    }

    function login_user($f3) {
        $db = $f3->get('DB');
        $user=new DB\SQL\Mapper($db,'docketminder_users');
        $auth=new \Auth($user, array('id'=>'username','pw'=>'password'));
        if($auth->login($f3->get('POST.username'),md5($f3->get('POST.password'))))
        {
            $user->load(array('username=?',$f3->get('POST.username')));
            $f3->set('SESSION.username', $user->username);
            $f3->set('SESSION.name', $user->name);
            $f3->set('SESSION.isLoggedIn', TRUE);
            $f3->reroute('/cases/all');
        }
        else
        {
            $f3->mset(array('title'=>'Login Error','content'=>'login.html','message' => 'Invalid username or password'));
            echo Template::instance()->render('main.html');
        }

    }

    function logout($f3) {
        session_start();
        session_destroy();
        $f3->reroute('/users/login');
    }

}
