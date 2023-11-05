<?php

namespace service;

use model\Service;
use RuntimeException;

class EntityService {

    private static array $servers = [];
    private static array $services = [];
    public static function getServers(): array {
        self::loadEntitiesWhenNecessary();
        return self::$servers;
    }

    public static function getServices(): array {
        self::loadEntitiesWhenNecessary();
        return self::$services;
    }

    public static function getService(string $id): Service|FALSE {
        $servicesWithId = array_filter(self::getServices(), function ($service) use ($id) {return $service->id === $id;});
        return array_shift($servicesWithId) ?? FALSE;
    }
    private static function loadEntitiesWhenNecessary(): void {
        if (sizeof(self::$services) == 0 || sizeof(self::$servers) == 0 || ConfigurationService::hasChanges(self::class))
            self::loadEntities();
    }

    private static function loadEntities(): void {
        $servers = ConfigurationService::get("servers", self::class);
        self::validateServersConfiguration($servers);
        self::$servers = self::removeDisabledServices($servers);
        self::$services = self::flatServiceLists(self::$servers);
    }

    static private function validateServersConfiguration(array $servers): void {
        $serverIds = [];
        $serviceIds = [];
        foreach ($servers as $server) {
            if (in_array($server->id, $serverIds))
                throw new RuntimeException("Configuration error. Server id '$server->id' exists multiple times");
            $serverIds[] = $server->id;
            foreach ($server->services as $service) {
                if (in_array($service->id, $serviceIds))
                    throw new RuntimeException("Configuration error. Service id '$service->id' exists multiple times");
                $serviceIds[] = $service->id;
            }
        }
    }

    private static function removeDisabledServices(array $servers): array {
        return array_map(function ($server) {
            $server->services = array_filter($server->services, function ($service) { return $service->enabled === TRUE; });
            return $server;
        }, $servers);
    }

    private static function flatServiceLists(array $servers): array {
        return array_reduce($servers, function ($carry, $server) {
            return array_merge($carry, $server->services);
        }, []);
    }
}