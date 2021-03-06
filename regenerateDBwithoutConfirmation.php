<?php

//session_cache_limiter(false);
//session_start();

date_default_timezone_set('Europe/Madrid');

error_reporting(E_ALL);

if(!function_exists('_')){
    function _($message){ return $message; }
}

print PHP_EOL.PHP_EOL.__DIR__.PHP_EOL;
$loader = require 'vendor/autoload.php';

Twig_Autoloader::register();

$em = new \app\models\EntityManager(true);

$em->dropDatabase();
$em->createDatabase();
$em->createTables();
$em->generateFixtures();

die("OK\n\n");
