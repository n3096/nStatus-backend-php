<?php

error_reporting(0);

require_once 'autoloader.php';

use service\ServiceCheckService;


header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['HTTP_X_FORWARDED_FOR'] != $_SERVER['SERVER_ADDR'])
    exit('{"status": "success"}');

try {
    $response = ServiceCheckService::checkService($_GET['id'], $_SERVER['HTTP_DATE']);
    if (!$response) throw new Exception();
} catch (Exception) {
    exit('{"status": "unknown"}');
}

header("Date: {$_SERVER['HTTP_DATE']}");
echo json_encode($response);