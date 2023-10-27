<?php

namespace service;

use Exception;
use model\configuration\LogFile;

class LogService {
    private function __construct(){}

    static public function info(LogFile $logFile, string $message): void {
        self::addLine($logFile, $message, "INFO");
    }

    static public function warning(LogFile $logFile, string $message): void {
        self::addLine($logFile, $message, "WARN");
    }

    static public function error(LogFile $logFile, string $message, ?Exception $exception = NULL): void {
        if ($exception)
            $message .= ": {$exception->getTraceAsString()}";
        self::addLine($logFile, $message, "ERROR");
    }

    static private function addLine(LogFile $logFile, string $message, string $prefix): void {
        $logFile = $logFile->getLogFilePath();
        $date = gmdate('Y-d-M H:i:s');
        $functionCallPoint = self::getFunctionCallOrder();

        FileService::append($logFile, "$date [$prefix] $message ($functionCallPoint)");
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