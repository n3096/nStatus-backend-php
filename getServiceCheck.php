<?php

error_reporting(0);
ignore_user_abort(true);
set_time_limit(300);

require_once 'autoloader.php';

use service\LogService;
use service\ServiceCheckService;


header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['HTTP_X_FORWARDED_FOR'] != $_SERVER['SERVER_ADDR']) {
    LogService::warning("Not allowed try to access by '{$_SERVER['HTTP_X_FORWARDED_FOR']}'");
    exit('{"status": "success"}');
}

try {
    $response = ServiceCheckService::checkService($_GET['id'], $_SERVER['HTTP_DATE']);
    if (!$response) throw new Exception();
} catch (Throwable $throwable) {
    LogService::error("Error when checking service", $throwable);
    exit('{"status": "unknown"}');
}

echo json_encode($response);