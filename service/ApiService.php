<?php

namespace service;

use Exception;
use model\configuration\LogFile;
use model\dto\ServiceDto;
use model\mapper\ArrayMapper;
use model\mapper\ServiceCheckMapper;
use model\Service;
use model\ServiceCheck;

class ApiService {
    static private string $BASE_PATH = __DIR__ . "/../api/";
    static private string $DEFAULT_NAME = 'index.json';
    private function __construct() {}

    static public function updateServices(array $servicesMap): void {
        $path = self::$BASE_PATH . 'services/';
        self::updateServicesEndpoint($path, array_keys($servicesMap));
        foreach ($servicesMap as $serviceId => $serviceCheck) {
            self::updateServiceEndpoints(EntityService::getService($serviceId), $serviceCheck);
        }
    }

    static private function updateServicesEndpoint(string $path, array $servicesIds): void {
        try {
            $path .= self::$DEFAULT_NAME;
            FileService::set($path, $servicesIds);
        } catch (Exception $exception) {
            LogService::error(LogFile::SERVICE_CHECK, "Error when updating API-file '$path'", $exception);
        }
    }

    static private function updateServiceEndpoints(Service $service, ServiceCheck $serviceCheck): void {
        try {
            $path = self::$BASE_PATH . "$service->id/";
            $serviceChecks = self::updateServiceChecks($path, $serviceCheck);
            self::updateService($path, $service, $serviceChecks);
        } catch (Exception $exception) {
            LogService::error(LogFile::SERVICE_CHECK, "Error when updating service '$service->id'", $exception);
        }
    }

    static private function updateServiceChecks(string $path, ServiceCheck $serviceCheck): array {
        $path .= 'service-check-history/full/' . self::$DEFAULT_NAME;
        $serviceChecks = FileService::exists($path) ? FileService::parseFile($path, ArrayMapper::map(ServiceCheckMapper::map())) : [];
        if (sizeof($serviceChecks) == 0 || self::hasStatusChanged($serviceChecks, $serviceCheck)) {
            $serviceChecks[] = $serviceCheck;
            FileService::set($path, $serviceChecks);
        }
        return $serviceChecks;
    }

    static private function hasStatusChanged(array $serviceChecks, ServiceCheck $serviceCheck): bool {
        if (sizeof($serviceChecks) == 0)
            return TRUE;
        usort($serviceChecks, function ($a, $b) {
            return strtotime($b->timestamp) - strtotime($a->timestamp);
        });
        return $serviceChecks[0]->status === $serviceCheck->status;
    }

    static private function updateService(string $path, Service $service, array $serviceChecks): void {
        $path .= self::$DEFAULT_NAME;
        FileService::set($path, json_encode(new ServiceDto($service, $serviceChecks)));
    }
}