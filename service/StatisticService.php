<?php

namespace service;

use model\statistics\ServicesCheckStatistics;
use RuntimeException;

class StatisticService {
    private static ServicesCheckStatistics $CHECK_STATISTICS;
    private static float $START_TIME;

    private function __construct() {}

    public static function startServicesCheckStatistics(): void {
        if (isset(self::$CHECK_STATISTICS))
            throw new RuntimeException("Statistics already started");

        LogService::debug("Starting statistic gathering");
        self::$CHECK_STATISTICS = new ServicesCheckStatistics();
        self::$START_TIME = microtime(TRUE);
    }
    public static function finishServicesCheckStatistics(): ServicesCheckStatistics {
        if (!isset(self::$CHECK_STATISTICS))
            throw new RuntimeException("Cannot finish statistics, that weren't startet yet");

        self::$CHECK_STATISTICS->finish(self::getRunTime(), self::getLogSize(), self::getApiSize());
        LogService::debug("Finished statistic gathering");
        return self::$CHECK_STATISTICS;
    }

    private static function getRunTime(): int {
        return intval((microtime(TRUE) - self::$START_TIME) * 1000); // convert to milliseconds
    }

    private static function getLogSize(): int {
        return FileService::getSize(LogService::getLogBasePath());
    }

    private static function getApiSize(): int {
        return FileService::getSize(ApiService::getBasePath());
    }
}