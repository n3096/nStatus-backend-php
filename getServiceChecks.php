<?php

error_reporting(0);
ignore_user_abort(true);
set_time_limit(120);

require_once 'autoloader.php';

use service\LogService;
use service\ServiceCheckService;

try {
    ServiceCheckService::checkServices();
} catch (Throwable $throwable) {
    LogService::error("Error when checking services", $throwable);
}