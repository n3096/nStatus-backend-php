<?php

namespace service;

use DateTime;
use model\configuration\LogFile;

class LogService {
    static private string $DATE_FORMAT = 'Y-m-d H:i:s';
    private function __construct(){}

    static public function info(LogFile $logFile, string $message): void {
        self::addLine($logFile, $message, "INFO");
    }

    static public function debug(LogFile $logFile, string $message): void {
        if (self::isDebug())
            self::addLine($logFile, $message, "DEBUG");
    }

    static private function isDebug(): bool {
        return ConfigurationService::get('isDebug', self::class);
    }

    static public function warning(LogFile $logFile, string $message): void {
        self::addLine($logFile, $message, "WARN");
    }

    static public function error(LogFile $logFile, string $message, ?\Throwable $throwable = NULL): void {
        $additionalLine = $throwable ? "#MESSAGE: {$throwable->getMessage()}\n{$throwable->getTraceAsString()}" : "";
        self::addLine($logFile, $message, "ERROR", $additionalLine);
    }

    static private function addLine(LogFile $logFile, string $message, string $prefix, string $additionalLine = ""): void {
        $logFile = $logFile->getLogFilePath();
        $date = (new DateTime())->format(self::$DATE_FORMAT);
        $functionCallPoint = self::getFunctionCallOrder();

        $suffix = !empty($additionalLine) ? "\n$additionalLine" : "";
        FileService::append($logFile, "$date [$prefix] $message ($functionCallPoint)$suffix");
    }

    static private function getFunctionCallOrder(): string {
        $functionCalls = [];
        foreach (debug_backtrace() as $debugStep) {
            if (isset($debugStep['file']) && $debugStep['file'] !== __FILE__)
                $functionCalls[] = $debugStep;
        }
        $functionCallOrder = array_map(function ($functionCall) {
            return basename($functionCall['file']) . ':' . $functionCall['line'];
        }, array_reverse($functionCalls));

        return join(">", $functionCallOrder) ?? '?';
    }
}