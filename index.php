<?php
    require 'vendor/autoload.php';

    $app = new DRouter\App();

    $app->route('/', 'GET', function(){
        echo 'Esta e a home em GET';
    })->setName('home');

    $app->route('/test', 'GET', function(){
        echo 'Esta é a minha rota /test, abaixo a execução do callable<br />';
        echo 'Da rota home:<br />';
        $callable = $this->getRouteCallable('home');
        $callable();
    });

    $app->run();