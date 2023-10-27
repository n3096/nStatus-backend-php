<?php

namespace service;

use Exception;
use helper\mapper\ArrayMapper;
use helper\mapper\ServerDtoMapper;
use helper\mapper\ServiceCheckMapper;
use model\configuration\LogFile;
use model\dto\ServiceDto;
use model\Service;
use model\ServiceCheck;

class ApiService {
    static private string $BASE_PATH = __DIR__ . "/../api/";
    static private string $DEFAULT_FILE_NAME = 'index.json';
    private function __construct() {}

    static public function updateServers(array $servers): void {
        try {
            $path = self::$BASE_PATH . 'servers/' . self::$DEFAULT_FILE_NAME;
            FileService::set($path, array_map(ServerDtoMapper::map(), $servers));
        } catch (Exception $exception) {
            LogService::error(LogFile::SERVICE_CHECK, "Error when updating servers api file", $exception);
        }
    }

    static public function updateServices(array $servicesMap): void {
        $path = self::$BASE_PATH . 'services/';
        $serviceDtos = [];
        foreach ($servicesMap as $serviceId => $serviceCheck) {
            if ($serviceDto = self::updateService($path, EntityService::getService($serviceId), $serviceCheck))
                $serviceDtos[] = $serviceDto;
        }
        self::updateServicesEndpoint($path, $serviceDtos);
    }

    static private function updateServicesEndpoint(string $path, array $serviceDtos): void {
        try {
            FileService::set($path . self::$DEFAULT_FILE_NAME, $serviceDtos);
        } catch (Exception $exception) {
            LogService::error(LogFile::SERVICE_CHECK, "Error when updating services api file", $exception);
        }
    }

    static private function updateService(string $path, Service $service, ServiceCheck $serviceCheck): ServiceDto|FALSE {
        try {
            $path .= "$service->id/";
            $serviceCheckHistory = self::updateServiceHistory($path, $serviceCheck);
            return self::updateServiceEndpoint($path, $service, $serviceCheckHistory);
        } catch (Exception $exception) {
            LogService::error(LogFile::SERVICE_CHECK, "Error when updating service '$service->id'", $exception);
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
        return $serviceChecks[0]->status === $serviceCheck->status;
    }

    static private function updateServiceEndpoint(string $path, Service $service, array $serviceChecks): ServiceDto {
        $serviceDto = new ServiceDto($service, $serviceChecks);
        FileService::set($path . self::$DEFAULT_FILE_NAME, json_encode($serviceDto));
        return $serviceDto;
    }
}