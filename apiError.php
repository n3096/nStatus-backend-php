<?php

$errorCode = $_GET['code'] ?? 404;
if (!is_int($errorCode) || $errorCode<300 || $errorCode>600)
    $errorCode = 404;

header('Content-Type: application/json; charset=utf-8');
http_response_code($errorCode);
echo "{'error':$errorCode}";