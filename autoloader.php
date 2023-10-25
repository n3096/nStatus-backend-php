<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(0);

spl_autoload_register(function ($className) {
    if (!file_exists($className))
        $className = str_replace('\\', '/', $className);
    require_once $className . '.php';
});