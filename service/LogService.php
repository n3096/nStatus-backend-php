<?php

namespace service;

use DateTime;
use Exception;
use model\configuration\LogFile;

class LogService {
    static private string $DATE_FORMAT = 'Y-m-d H:i:s';
    private function __construct(){}

    static public function info(LogFile $logFile, string $message): void {
        self::addLine($logFile, $message, "INFO");
    }

    static public function warning(LogFile $logFile, string $message): void {
        self::addLine($logFile, $message, "WARN");
    }

    static public function error(LogFile $logFile, string $message, ?Exception $exception = NULL): void {
        $additionalLine = $exception ? "#MESSAGE: {$exception->getMessage()}\n{$exception->getTraceAsString()}" : "";
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