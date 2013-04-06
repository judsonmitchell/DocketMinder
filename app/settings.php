<?php

class Settings {

    function beforeRoute($f3){

        if (!$f3->get('SESSION.isLoggedIn')) {

            $f3->error(401);
        }

    }

    function index($f3) {
        echo 'test';


    }
}
