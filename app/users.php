<?php

class Users {

    function signup($f3) {

        $f3->mset(array('title'=>'New Account','content'=>'new_user.html','header'=>'header.html','message'=>FALSE));
        echo Template::instance()->render('main.html');

    }

    function add($f3) {
        $db = $f3->get('DB');
        $f3->set('user',new DB\SQL\Mapper($db,'docketminder_users'));
        $f3->get('user')->copyFrom('POST');
        $f3->get('user')->password = md5($f3->get('POST.password'));
        $f3->get('user')->save();
        $f3->reroute('/cases/all');

    }

    function login($f3) {

        $f3->mset(array('title'=>'Login','header'=>'header.html','content'=>'login.html','message'=>FALSE));
        echo Template::instance()->render('main.html');

    }

    function login_user($f3) {
        $db = $f3->get('DB');
        $user=new DB\SQL\Mapper($db,'docketminder_users');
        $auth=new \Auth($user, array('id'=>'email','pw'=>'password'));
        if($auth->login($f3->get('POST.email'),md5($f3->get('POST.password'))))
        {
            $user->load(array('email=?',$f3->get('POST.email')));
            $f3->set('SESSION.email', $user->email);
            $f3->set('SESSION.name', $user->name);
            $f3->set('SESSION.isLoggedIn', TRUE);
            $f3->reroute('/cases/all');
        }
        else
        {
            $f3->mset(array('title'=>'Login Error','header'=>'header.html','content'=>'login.html','message' => 'Invalid email or password'));
            echo Template::instance()->render('main.html');
        }

    }

    function logout($f3) {
        session_start();
        session_destroy();
        $f3->reroute('/users/login');
    }

    //Password reset methods
    function reset($f3) {

        $f3->mset(array('title'=>'Forgot Password','header'=>'header.html','content'=>'forgot.html','message'=>FALSE));
        echo Template::instance()->render('main.html');
    }

    function check_email($f3) {

        $db = $f3->get('DB');
        $user=new DB\SQL\Mapper($db,'docketminder_users');
        if($user->count(array('email=?',$f3->get('POST.forgot_email')))){

            $response = array('status' => 'success','message' => 'This account exists.');
            echo json_encode($response);
        }
        else {

            $response = array('status' => 'fail','message' => 'Sorry, we have no account with that email address.');
            echo json_encode($response);

        }
    }
}
