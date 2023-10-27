<?php

namespace model\configuration;

enum LogFile: string {
    case SERVICE_MULTITHREADING = "service-multithreading";
    case SERVICE_CHECK = "service-check";
    case FILE_ACCESS = "files-access";
    case MAPPING = "mapping";
    case CONFIGURATION = "configuration";

    public function getLogFilePath(): string {
        return __DIR__ . "/../../logs/$this->value.log";
    }
}