<?php

namespace service;

use DateTime;
use Throwable;

class LogService {
    private static string $LOG_BASE_PATH = __DIR__ . '/../logs/';
    private static string $DATE_FORMAT = 'Y-m-d H:i:s';
    private static bool|null $IS_DEBUG_MODE = null;
    private function __construct(){}

    public static function getLogBasePath(): string {
        return self::$LOG_BASE_PATH;
    }

    public static function info(mixed $message): void {
        self::addLine($message, "INFO");
    }

    public static function debug(mixed $message): void {
        if (self::isDebugMode())
            self::addLine($message, "DEBUG");
    }

    private static function isDebugMode(): bool {
        return self::$IS_DEBUG_MODE ?? self::$IS_DEBUG_MODE = ConfigurationService::get('isDebug', self::class);
    }

    public static function warning(string $message): void {
        self::addLine($message, "WARN");
    }

    public static function error(mixed $message, ?Throwable $throwable = NULL): void {
        $additionalLine = $throwable ? "#MESSAGE: {$throwable->getMessage()}\n{$throwable->getTraceAsString()}" : "";
        self::addLine($message, "ERROR", $additionalLine);
    }

    private static function addLine(mixed $message, string $prefix, string $additionalLine = ""): void {
        if (!is_string($message))
            $message = json_encode($message);

        $logFile = self::$LOG_BASE_PATH . date("Y-m-d") . '.log';
        $date = (new DateTime())->format(self::$DATE_FORMAT);
        $functionCallPoint = self::getFunctionCallOrder();

        $suffix = !empty($additionalLine) ? "\n$additionalLine" : "";
        FileService::append($logFile, "$date [$prefix] $message ($functionCallPoint)$suffix");
    }

    private static function getFunctionCallOrder(): string {
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