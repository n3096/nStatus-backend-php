<?php

namespace service;

use model\Service;

class EntityService {

    static private array $servers = [];
    static private array $services = [];
    static public function getServers(): array {
        self::loadEntitiesWhenNecessary();
        return self::$servers;
    }

    static public function getServices(): array {
        self::loadEntitiesWhenNecessary();
        return self::$services;
    }

    static public function getService(string $id): Service|FALSE {
        $servicesWithId = array_filter(self::getServices(), function ($service) use ($id) {return $service->id === $id;});
        return array_shift($servicesWithId) ?? FALSE;
    }
    static private function loadEntitiesWhenNecessary(): void {
        if (sizeof(self::$services) == 0 || sizeof(self::$servers) == 0 || ConfigurationService::hasChanges(self::class))
            self::loadEntities();
    }

    static private function loadEntities(): void {
        $servers = ConfigurationService::get("servers", self::class);
        self::$servers = self::removeDisabledServices($servers);
        self::$services = self::flatServiceLists(self::$servers);
    }

    static private function removeDisabledServices(array $servers): array {
        return array_map(function ($server) {
            $server->services = array_filter($server->services, function ($service) { return $service->enabled === TRUE; });
            return $server;
        }, $servers);
    }

    static private function flatServiceLists(array $servers): array {
        return array_reduce($servers, function ($carry, $server) {
            return array_merge($carry, $server->services);
        }, []);
    }
}