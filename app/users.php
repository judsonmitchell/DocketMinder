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
    function request_reset($f3) {

        $f3->mset(array('title'=>'Forgot Password','header'=>'header.html','content'=>'forgot.html','message'=>FALSE));
        echo Template::instance()->render('main.html');
    }

    function check_email($f3) {

        $db = $f3->get('DB');
        $user=new DB\SQL\Mapper($db,'docketminder_users');
        if($user->count(array('email=?',$f3->get('POST.forgot_email')))){

            $response = array('status' => 'success','message' => 'Good, this account exists.');
            echo json_encode($response);
        }
        else {

            $response = array('status' => 'fail','message' => 'Sorry, we have no account with that email address.');
            echo json_encode($response);

        }
    }

    function reset_stage($f3) {

        $db = $f3->get('DB');
        $user=new DB\SQL\Mapper($db,'docketminder_users');
        $user->load(array('email=?',$f3->get('POST.forgot_email')));
        $key = $this->gen_key(20);
        $user->reset_key = $key;
        $user->save();

        //email
        $postmark = new Postmark($f3->get('postmark_key'),$f3->get('postmark_email'));
        $message = "We have received a request to reset your password on DocketMinder.  To change your password, please visit " . $f3->get('base_url') . "/users/new_password_prompt/" . $key . "\n\n If you did not request this password change, please ignore this email.";
        $mail = $postmark->to($f3->get('POST.forgot_email'))
        ->subject('DocketMinder: Reset Your Password')
        ->plain_message($message)
        ->send();

        //notify
        $message = "Thanks. An email has been sent to <strong>" . $f3->get('POST.forgot_email') . "</strong> with
        instructions on how to reset your password."; 
       
        $response = array('status'=>'success','message'=>$message);

        echo json_encode($response);
    }

    function new_password_prompt ($f3){
        $db = $f3->get('DB');
        $user=new DB\SQL\Mapper($db,'docketminder_users');
        if($user->count(array('reset_key=?',$f3->get('PARAMS.key')))){
            $f3->mset(array('title'=>'Forgot Password','header'=>'header.html','content'=>'new_password_prompt.html','message'=>FALSE));
            echo Template::instance()->render('main.html');
        }
        else {
            $f3->error(404);
        }
    }

    function new_password_do ($f3){

        $db = $f3->get('DB');
        $user=new DB\SQL\Mapper($db,'docketminder_users');
        $user->load(array('reset_key=?',$f3->get('POST.reset_key')));
        if (!$user->dry()) {
            $user->password = md5($f3->get('POST.password'));
            $user->reset_key = '';
            $user->save();
            $message = 'Password reset successful.';
            $f3->mset(array('title'=>'Password Reset Successful','header'=>'header.html','content'=>'new_password_success.html','message'=>$message));
            echo Template::instance()->render('main.html');
        }
        else {

            $f3->error(403);
        }

    }

    function gen_key($length){

        $underscores = 2; // Maximum number of underscores allowed in password

        $p ="";
        for ($i=0;$i<$length;$i++)
        {
            $c = mt_rand(1,7);
            switch ($c)
            {
                case ($c<=2):
                    // Add a number
                    $p .= mt_rand(0,9);
                    break;
                case ($c<=4):
                    // Add an uppercase letter
                    $p .= chr(mt_rand(65,90));
                    break;
                case ($c<=6):
                    // Add a lowercase letter
                    $p .= chr(mt_rand(97,122));
                    break;
                case 7:
                    $len = strlen($p);
                    if ($underscores>0&&$len>0&&$len<9&&$p[$len-1]!="_")
                    {
                        $p .= "_";
                        $underscores--;
                    }
                    else
                    {
                        $i--;
                        continue;
                    }
                    break;
            }
        }
        return $p;
    }
}
