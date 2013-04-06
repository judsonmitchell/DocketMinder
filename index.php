<?php

$f3=require('lib/base.php');
$f3->set('DEBUG',2);
$f3->set('UI','ui/,ui/templates/');
$f3->set('AUTOLOAD','app/');
$f3->config('config.ini');
$f3->set('DB', new DB\SQL('mysql:host='. $f3->get('db_host') .
';port=3306;dbname=' . $f3->get('db_name') , $f3->get('db_user'),$f3->get('db_pass')));             

//main route
$f3->route('GET /',
	function($f3) {
        $f3->reroute('/users/login');
    }
);

//user routes
$f3->route('POST /users/login_user','Users->login_user');
$f3->route('POST /users/add','Users->add');
$f3->route('GET /users/new_password_prompt/@key','Users->new_password_prompt');
$f3->route('GET|POST /users/@action','Users->@action');

//case routes
$f3->route('GET|POST /cases/@action','Cases->@action');
$f3->route('GET /cases/@action/@id','Cases->@action');
$f3->route('GET /opcso/@casenum',
    function($f3){
        $DM = new DocketMaster('http://www.opcso.org/dcktmstr/666666.php?&docase=' . $f3->get('PARAMS.casenum'));
        if ($DM->error){
            $f3->error(404);
        }
        else {
            $DefendantsBlock = $DM->getDefendantBlock();
            $defendants = $DM->parseDefendantBlock($DefendantsBlock);
            echo $defendants[0]->getFirstName() . " " .  $defendants[0]->getLastName();
            if (count($defendants) > 1){echo " et. al.";}
        }
    });

//settings routes
$f3->route('GET|POST /settings/@action','Settings->@action');

//static routes
$f3->route('GET /about',
    function($f3) {
        $f3->mset(array('title'=>'New Account','content'=>'about.html','header'=>'header.html','message'=>FALSE));
        echo Template::instance()->render('main.html');
    }

);
$f3->run();
