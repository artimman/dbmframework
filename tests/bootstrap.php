<?php

// Test command: vendor/bin/phpunit tests/Unit/ExampleUnitTest.php
// Definiujemy stałe wymagane w projekcie, np. BASE_DIRECTORY
if (!defined('BASE_DIRECTORY')) {
    define('DS', DIRECTORY_SEPARATOR);
    define('BASE_DIRECTORY', dirname(__DIR__) . DS);
}

// Wczytujemy autoload z Composer
$pathAutoload = BASE_DIRECTORY . 'vendor' . DS . 'autoload.php';
require_once($pathAutoload);
