<?php

namespace model\configuration;

enum LogFile: string {
    case SERVICE_MULTITHREADING = "service-multithreading";
    case SERVICE_CHECK = "service-check";
    case FILE_ACCESS = "files-access";
    case MAPPING = "mapping";
    case CONFIGURATION = "configuration";
    case STATISTICS = "statistics";

    public function getLogFilePath(): string {
        return self::getLogBasePath() . "$this->value.log";
    }
    static public function getLogBasePath(): string {
        return __DIR__ . '/../../logs/';
    }
}