<?php

namespace service;

use Exception;
use model\Server;
use model\Service;
use model\SocketProtocol;

class ConfigurationService {
    static private string $CONFIGURATION_FILE = __DIR__ . '/../configuration.json';
    static private int $configurationFileLastChanged = 0;
    static private array $servers = [];
    static private array $services = [];

    private function __construct() {
    }

    static public function getServers(): array {
        if (sizeof(self::$servers) == 0 || self::hasConfigurationFileChanged())
            self::load();
        return self::$servers;
    }

    static public function getServices(): array {
        if (sizeof(self::$services) == 0 || self::hasConfigurationFileChanged())
            self::load();
        return self::$services;
    }

    static private function hasConfigurationFileChanged(): bool {
        $configurationFileLastChanged = self::$configurationFileLastChanged;
        self::$configurationFileLastChanged = filemtime(self::$CONFIGURATION_FILE);
        return $configurationFileLastChanged != self::$configurationFileLastChanged;
    }

    static private function load(): void {
        $rawConfiguration = file_get_contents(self::$CONFIGURATION_FILE);
        $configuration = json_decode($rawConfiguration, true);
        self::validateConfiguration($configuration);

        self::$servers = self::parseServers($configuration["servers"]);
        foreach (self::$servers as $server) {
            foreach ($server->services as $service) {
                self::$services[] = $service;
            }
        }
    }

    /**
     * @throws Exception as validation check
     */
    static private function validateConfiguration($configuration): void {
        if (empty($configuration))
            throw new Exception("Configuration is empty");
        if (!is_array($configuration))
            throw new Exception("Configuration is no array");

        if (!isset($configuration["servers"]))
            throw new Exception("Configuration is missing servers");
        if (!is_array($configuration["servers"]))
            throw new Exception("Configuration of servers is no array");
    }

    /**
     * @throws Exception as validation check
     */
    static private function parseServers(array $serversArray): array {
        $servers = [];
        foreach ($serversArray as $server) {
            if (!isset($server["id"]))
                throw new Exception("Server is missing 'id'");
            if (!self::isValidUUID($server["id"]))
                throw new Exception("Server with id '{$server["id"]}' has invalid uuid");

            if (!isset($server["name"]))
                throw new Exception("Server with id '{$server["id"]}' is missing 'name'");
            if (!is_string($server["name"]))
                throw new Exception("Server with id '{$server["id"]}' has no 'name' of type string");

            if (!isset($server["services"]))
                throw new Exception("Server with id '{$server["id"]}' is missing 'services'");
            if (!is_array($server["services"]))
                throw new Exception("Server with id '{$server["id"]}' has no 'services' of type array");

            $servers[] = new Server($server["id"], $server["name"], self::parseServices($server["services"]));
        }
        return $servers;
    }

    /**
     * @throws Exception as validation check
     */
    static private function parseServices(array $servicesArray): array {
        $services = [];
        foreach ($servicesArray as $service) {
            if (!isset($service["id"]))
                throw new Exception("Service is missing 'id'");
            if (!self::isValidUUID($service["id"]))
                throw new Exception("Service with id '{$service["id"]}' has invalid uuid");

            if (!isset($service["name"]))
                throw new Exception("Service with id '{$service["id"]}' is missing 'name'");
            if (!is_string($service["name"]))
                throw new Exception("Service with id '{$service["id"]}' has no 'name' of type string");

            if (!isset($service["hostName"]))
                throw new Exception("Service with id '{$service["id"]}' is missing 'hostName'");
            if (!is_string($service["hostName"]))
                throw new Exception("Service with id '{$service["id"]}' has no 'hostName' of type string");

            if (!isset($service["socketProtocol"]))
                throw new Exception("Service with id '{$service["id"]}' is missing 'socketProtocol'");
            $service["socketProtocol"] = SocketProtocol::parse($service["socketProtocol"]);

            if (!isset($service["port"]))
                throw new Exception("Service with id '{$service["id"]}' is missing 'port'");
            if (!is_int($service["port"]))
                throw new Exception("Service with id '{$service["id"]}' has no 'port' of type int");

            if (!isset($service["enabled"]))
                throw new Exception("Service with id '{$service["id"]}' is missing 'enabled'");
            if (!is_bool($service["enabled"]))
                throw new Exception("Service with id '{$service["id"]}' has no 'enabled' of type bool");

            if (!isset($service["timeout"]))
                throw new Exception("Service with id '{$service["id"]}' is missing 'timeout'");
            if (!is_int($service["timeout"]))
                throw new Exception("Service with id '{$service["id"]}' has no 'timeout' of type int");

            $services[] = new Service($service["id"], $service["name"], $service["hostName"], $service["socketProtocol"], $service["port"], $service["enabled"], $service["timeout"]);
        }
        return $services;
    }

    static function isValidUUID(string $uuid): bool {
        $pattern = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/';
        return preg_match($pattern, $uuid) === 1;
    }
}