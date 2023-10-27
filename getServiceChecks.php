<?php

error_reporting(0);
ignore_user_abort(true);
set_time_limit(120);

require_once 'autoloader.php';

use model\configuration\LogFile;
use service\LogService;
use service\ServiceCheckService;

try {
    ServiceCheckService::checkServices();
} catch (Exception $exception) {
    LogService::error(LogFile::SERVICE_CHECK, "Error when checking services", $exception);
}