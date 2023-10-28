<?php

namespace service;

use model\configuration\LogFile;
use model\statistics\ServicesCheckStatistics;
use RuntimeException;

class StatisticService {
    static private ServicesCheckStatistics $CHECK_STATISTICS;
    static private float $START_TIME;

    private function __construct() {}

    static public function startServicesCheckStatistics(): void {
        if (isset(self::$CHECK_STATISTICS))
            throw new RuntimeException("Statistics already started");

        LogService::debug(LogFile::STATISTICS, "Starting statistic gathering");
        self::$CHECK_STATISTICS = new ServicesCheckStatistics();
        self::$START_TIME = microtime(TRUE);
    }
    static public function finishServicesCheckStatistics(): ServicesCheckStatistics {
        if (!isset(self::$CHECK_STATISTICS))
            throw new RuntimeException("Cannot finish statistics, that weren't startet yet");

        self::$CHECK_STATISTICS->finish(self::getRunTime(), self::getLogSize(), self::getApiSize());
        LogService::debug(LogFile::STATISTICS, "Finished statistic gathering");
        return self::$CHECK_STATISTICS;
    }

    static private function getRunTime(): int {
        return intval((microtime(TRUE) - self::$START_TIME) * 1000); // convert to milliseconds
    }

    static private function getLogSize(): int {
        return FileService::getSize(LogFile::getLogBasePath());
    }

    static private function getApiSize(): int {
        return FileService::getSize(ApiService::getBasePath());
    }
}