<?php

namespace service;

use DateInterval;
use helper\mapper\ArrayMapper;
use helper\mapper\ServerDtoMapper;
use helper\mapper\ServiceCheckMapper;
use helper\mapper\ServiceDtoMapper;
use model\DateTimeSerializable;
use model\dto\ApiInformationDto;
use model\dto\ServiceDto;
use model\Service;
use model\ServiceCheck;
use model\statistics\ServicesCheckStatistics;
use model\Status;
use Throwable;

class ApiService {
    private static string $BASE_PATH = __DIR__ . "/../api/";
    private static string $DEFAULT_FILE_NAME = 'index.json';
    private static string|null $CONFIGURATION_UPDATE_INTERVAL = null;
    private function __construct() {}
    public static function getBasePath(): string {
        return self::$BASE_PATH;
    }

    public static function updateStatistics(ServicesCheckStatistics $servicesCheckStatistics): void {
        $path = self::$BASE_PATH . 'statistics/';
        self::updateServicesCheckStatisticsHistory($path, $servicesCheckStatistics);
        self::updateServicesCheckStatisticsRunTimestamps($path, $servicesCheckStatistics->timestamp);
    }

    private static function updateServicesCheckStatisticsHistory(string $path, ServicesCheckStatistics $checksStatistics): void {
        try {
            $path .= 'servicesCheckHistory/';
            FileService::append($path . 'index.csv', $checksStatistics);
        } catch (Throwable $throwable) {
            LogService::error("Error when updating servicesCheckHistory statistics file ", $throwable);
        }
    }

    private static function updateServicesCheckStatisticsRunTimestamps(string $path, DateTimeSerializable $timestamp): void {
        try {
            $filePath = $path . "runs/timestamps/" . self::$DEFAULT_FILE_NAME;
            $timestamps = FileService::exists($filePath) ? FileService::parseFile($filePath, ArrayMapper::map(new DateTimeSerializable())) : [];
            $timestamps[] = $timestamp;
            FileService::set($filePath, $timestamps);
        } catch (Throwable $throwable) {
            LogService::error("Error when updating timestamps for runs statistics file", $throwable);
        }
    }

    public static function updateApiInformation(ServicesCheckStatistics $checksStatistics, array $services): void {
        try {
            $maxTimeout = max(array_map(function ($service) {return $service->timeout;}, $services));
            $apiInformation = new ApiInformationDto($checksStatistics->logSize, $checksStatistics->apiSize, $maxTimeout, self::getUpdateInterval(), $checksStatistics->runTime);
            FileService::set(self::$BASE_PATH . self::$DEFAULT_FILE_NAME, $apiInformation);
        } catch (Throwable $throwable) {
            LogService::error("Error when updating api base information file ", $throwable);
        }
    }

    private static function getUpdateInterval(): int {
        return self::$CONFIGURATION_UPDATE_INTERVAL ?? self::$CONFIGURATION_UPDATE_INTERVAL = ConfigurationService::get('updateInterval', self::class) ?? 300;
    }

    public static function updateServers(array $servers): void {
        try {
            $path = self::$BASE_PATH . 'servers/' . self::$DEFAULT_FILE_NAME;
            FileService::set($path, array_map(ServerDtoMapper::map(), $servers));
        } catch (Throwable $throwable) {
            LogService::error("Error when updating servers api file", $throwable);
        }
    }

    public static function updateServices(array $serviceMaps): void {
        $path = self::$BASE_PATH . 'services/';
        $serviceMapResult = [];

        foreach ($serviceMaps as $serviceMap) {
            $serviceDto = self::tryUpdateService($path, $serviceMap['service'], $serviceMap['serviceCheck']);
            $serviceMapResult[] = $serviceDto;
        }
        self::updateServicesEndpoint($path, $serviceMapResult);
    }

    private static function tryUpdateService(string $path, Service $service, ServiceCheck $serviceCheck): ServiceDto {
        $path .= "$service->id/";
        $currentServiceDto = self::getServiceEndpoint($path);
        try {
            $serviceCheckHistory = self::updateServiceHistory($path, $serviceCheck, $currentServiceDto);
            return self::updateServiceEndpoint($path, $service, $serviceCheck->status, $serviceCheckHistory);
        } catch (Throwable $throwable) {
            LogService::error("Error when updating service '$service->id'", $throwable);
        }
        return $currentServiceDto;
    }

    private static function updateServiceHistory(string $path, ServiceCheck $serviceCheck, ServiceDto|FALSE $currentServiceDto): array {
        $filePath = $path . "service-check-history/full/" . self::$DEFAULT_FILE_NAME;
        $serviceChecks = FileService::exists($filePath) ? FileService::parseFile($filePath, ArrayMapper::map(ServiceCheckMapper::map())) : [];

        if ($hasChanges = self::createStatusCheckUnknownWhenLatestUpdateWasTooFar($serviceCheck, $currentServiceDto))
            $serviceChecks[] = $hasChanges;
        if ($hasChanges = $hasChanges || self::hasStatusChanged($serviceCheck, $serviceChecks))
            $serviceChecks[] = $serviceCheck;
        if ($hasChanges) FileService::set($filePath, $serviceChecks);
        return $serviceChecks;
    }

    private static function createStatusCheckUnknownWhenLatestUpdateWasTooFar(ServiceCheck $latestServiceDto, ServiceDto|FALSE $currentServiceDto): ServiceCheck|FALSE {
        if (!$currentServiceDto)
            return FALSE;

        if ($latestServiceDto->status == Status::UNKNOWN)
            return FALSE;

        $updateIntervalTolerance = self::getUpdateInterval() * 2;
        $secondsSinceLatestUpdate = time() - $currentServiceDto->latestUpdate->getTimestamp();
        if ($secondsSinceLatestUpdate < $updateIntervalTolerance)
            return FALSE;

        $timestamp = $currentServiceDto->latestUpdate;
        $timestamp->add(DateInterval::createFromDateString("$updateIntervalTolerance seconds"));

        LogService::warning("Add Status 'UNKNOWN' to Service with id '$currentServiceDto->id' due to inactivity on status checks for '$secondsSinceLatestUpdate' seconds");
        return new ServiceCheck($latestServiceDto->hostName,
            $latestServiceDto->port,
            $latestServiceDto->socketProtocol,
            $latestServiceDto->fullHostName,
            $timestamp,
            0,
            'unknown',
            'unknown',
            'unknown',
            Status::UNKNOWN,
            [],
            ["Added this status due to data cleansing by server"]);
    }

    private static function hasStatusChanged(ServiceCheck $currentServiceCheck, array $serviceChecks): bool {
        $latestServiceCheck = self::getLatestServiceCheck($serviceChecks);
        return !$serviceChecks || $currentServiceCheck->status !== $latestServiceCheck->status;
    }

    private static function getLatestServiceCheck(array $serviceChecks): ServiceCheck|FALSE {
        usort($serviceChecks, function ($a, $b) {
            return $b->timestamp->getTimestamp() - $a->timestamp->getTimestamp();
        });
        return $serviceChecks[0] ?? FALSE;
    }

    private static function updateServiceEndpoint(string $path, Service $service, Status $status, array $serviceChecks): ServiceDto {
        $serviceDto = ServiceDto::of($service, $status, $serviceChecks);
        FileService::set($path . self::$DEFAULT_FILE_NAME, $serviceDto);
        return $serviceDto;
    }

    private static function getServiceEndpoint(string $path): ServiceDto|FALSE {
        $path .= self::$DEFAULT_FILE_NAME;
        return FileService::exists($path) ? FileService::parseFile($path, ServiceDtoMapper::map()) : FALSE;
    }

    private static function updateServicesEndpoint(string $path, array $serviceDtos): void {
        try {
            FileService::set($path . self::$DEFAULT_FILE_NAME, $serviceDtos);
        } catch (Throwable $throwable) {
            LogService::error("Error when updating services api file", $throwable);
        }
    }
}