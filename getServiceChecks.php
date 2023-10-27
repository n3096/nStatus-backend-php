<?php

error_reporting(0);

require_once 'autoloader.php';

use model\configuration\LogFile;
use service\LogService;
use service\ServiceCheckService;

try {
    ServiceCheckService::checkServices();
} catch (Exception $exception) {
    LogService::error(LogFile::SERVICE_CHECK, "Error when checking services", $exception);
}