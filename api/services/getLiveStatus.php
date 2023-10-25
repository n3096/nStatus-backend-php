<?php

use model\Status;
use service\ServiceCheckService;

$DEFAULT_RESPONSE = '{"status": "' . Status::REACHABLE->value . '"}';
if ($_SERVER['HTTP_X_FORWARDED_FOR'] != $_SERVER['SERVER_ADDR'])
    return $DEFAULT_RESPONSE;
echo ServiceCheckService::checkService($_GET['id'], $_SERVER['DATE']) ?? $DEFAULT_RESPONSE;