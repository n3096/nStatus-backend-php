<?php

namespace service;

use model\configuration\LogFile;
use model\mapper\ConfigurationMapper;
use model\Service;

class EntityService {
    static private string $CONFIGURATION_FILE = __DIR__ . '/../configuration.json';
    static private int $configurationFileLastChanged = 0;
    static private array $servers = [];
    static private array $services = [];

    private function __construct() {}

    static public function getServers(): array {
        self::loadConfigurationWhenNecessary();
        return self::$servers;
    }

    static public function getServices(): array {
        self::loadConfigurationWhenNecessary();
        return self::$services;
    }

    static public function getService(string $id): Service|FALSE {
        self::loadConfigurationWhenNecessary();
        return array_filter(self::$services, function ($service) use ($id) {return $service->id === $id;})[0] ?? FALSE;
    }

    static private function loadConfigurationWhenNecessary(): void {
        if (sizeof(self::$services) == 0 || sizeof(self::$servers) == 0 || self::hasConfigurationFileChanged())
            self::load();
    }

    static private function hasConfigurationFileChanged(): bool {
        $configurationFileLastChanged = self::$configurationFileLastChanged;
        self::$configurationFileLastChanged = filemtime(self::$CONFIGURATION_FILE);
        return $configurationFileLastChanged != self::$configurationFileLastChanged;
    }

    static public function load(): void {
        LogService::info(LogFile::CONFIGURATION, 'Load configuration');

        $configuration = FileService::parseFile(self::$CONFIGURATION_FILE, new ConfigurationMapper());
        self::$servers = $configuration->servers;
        self::$services = array_reduce( self::$servers, function ($carry, $server) {
            return array_merge($carry, $server->services);
        }, []);
        var_dump(self::$services);
    }
}