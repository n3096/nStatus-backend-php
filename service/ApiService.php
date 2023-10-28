<?php

namespace service;

use helper\mapper\ArrayMapper;
use helper\mapper\ServerDtoMapper;
use helper\mapper\ServiceCheckMapper;
use model\configuration\LogFile;
use model\DateTimeSerializable;
use model\dto\ApiInformationDto;
use model\dto\ServiceDto;
use model\Service;
use model\ServiceCheck;
use model\statistics\ServicesCheckStatistics;
use Throwable;

class ApiService {
    static private string $BASE_PATH = __DIR__ . "/../api/";
    static private string $DEFAULT_FILE_NAME = 'index.json';
    private function __construct() {}
    static public function getBasePath(): string {
        return self::$BASE_PATH;
    }

    static public function updateStatistics(ServicesCheckStatistics $servicesCheckStatistics): void {
        $path = self::$BASE_PATH . 'statistics/';
        self::updateServicesCheckStatisticsHistory($path, $servicesCheckStatistics);
        self::updateServicesCheckStatisticsRunTimestamps($path, $servicesCheckStatistics->timestamp);
    }

    static private function updateServicesCheckStatisticsHistory(string $path, ServicesCheckStatistics $checksStatistics): void {
        try {
            $path .= 'servicesCheckHistory/';
            FileService::append($path . 'index.csv', $checksStatistics);
        } catch (Throwable $throwable) {
            LogService::error(LogFile::SERVICE_CHECK, "Error when updating servicesCheckHistory statistics file ", $throwable);
        }
    }

    static private function updateServicesCheckStatisticsRunTimestamps(string $path, DateTimeSerializable $timestamp): void {
        try {
            $filePath = $path . "runs/timestamps/" . self::$DEFAULT_FILE_NAME;
            $timestamps = FileService::exists($filePath) ? FileService::parseFile($filePath, ArrayMapper::map(new DateTimeSerializable())) : [];
            $timestamps[] = $timestamp;
            FileService::set($filePath, $timestamps);
        } catch (Throwable $throwable) {
            LogService::error(LogFile::SERVICE_CHECK, "Error when updating timestamps for runs statistics file", $throwable);
        }
    }

    static public function updateApiInformation(ServicesCheckStatistics $checksStatistics, array $services): void {
        try {
            $updateInterval = ConfigurationService::get('updateInterval', self::class);
            $maxTimeout = max(array_map(function ($service) {return $service->timeout;}, $services));
            $apiInformation = new ApiInformationDto($checksStatistics->logSize, $checksStatistics->apiSize, $maxTimeout, $updateInterval);
            FileService::set(self::$BASE_PATH . self::$DEFAULT_FILE_NAME, $apiInformation);
        } catch (Throwable $throwable) {
            LogService::error(LogFile::SERVICE_CHECK, "Error when updating api base information file ", $throwable);
        }
    }

    static public function updateServers(array $servers): void {
        try {
            $path = self::$BASE_PATH . 'servers/' . self::$DEFAULT_FILE_NAME;
            FileService::set($path, array_map(ServerDtoMapper::map(), $servers));
        } catch (Throwable $throwable) {
            LogService::error(LogFile::SERVICE_CHECK, "Error when updating servers api file", $throwable);
        }
    }

    static public function updateServices(array $servicesMap): void {
        $path = self::$BASE_PATH . 'services/';
        $serviceDtos = [];
        foreach ($servicesMap as $serviceMap) {
            if ($serviceDto = self::updateService($path, $serviceMap['service'], $serviceMap['serviceCheck']))
                $serviceDtos[] = $serviceDto;
        }
        self::updateServicesEndpoint($path, $serviceDtos);
    }

    static private function updateServicesEndpoint(string $path, array $serviceDtos): void {
        try {
            FileService::set($path . self::$DEFAULT_FILE_NAME, $serviceDtos);
        } catch (Throwable $throwable) {
            LogService::error(LogFile::SERVICE_CHECK, "Error when updating services api file", $throwable);
        }
    }

    static private function updateService(string $path, Service $service, ServiceCheck $serviceCheck): ServiceDto|FALSE {
        try {
            $path .= "$service->id/";
            $serviceCheckHistory = self::updateServiceHistory($path, $serviceCheck);
            return self::updateServiceEndpoint($path, $service, $serviceCheckHistory);
        } catch (Throwable $throwable) {
            LogService::error(LogFile::SERVICE_CHECK, "Error when updating service '$service->id'", $throwable);
            return FALSE;
        }
    }

    static private function updateServiceHistory(string $path, ServiceCheck $serviceCheck): array {
        $filePath = $path . "service-check-history/full/" . self::$DEFAULT_FILE_NAME;
        $serviceChecks = FileService::exists($filePath) ? FileService::parseFile($filePath, ArrayMapper::map(ServiceCheckMapper::map())) : [];
        if (self::hasStatusChanged($serviceChecks, $serviceCheck)) {
            $serviceChecks[] = $serviceCheck;
            FileService::set($filePath, $serviceChecks);
        }
        return $serviceChecks;
    }

    static private function hasStatusChanged(array $serviceChecks, ServiceCheck $serviceCheck): bool {
        if (sizeof($serviceChecks) == 0)
            return TRUE;
        usort($serviceChecks, function ($a, $b) {
            return $b->timestamp->getTimestamp() - $a->timestamp->getTimestamp();
        });
        return $serviceChecks[0]->status !== $serviceCheck->status;
    }

    static private function updateServiceEndpoint(string $path, Service $service, array $serviceChecks): ServiceDto {
        $serviceDto = new ServiceDto($service, $serviceChecks);
        FileService::set($path . self::$DEFAULT_FILE_NAME, json_encode($serviceDto));
        return $serviceDto;
    }
}