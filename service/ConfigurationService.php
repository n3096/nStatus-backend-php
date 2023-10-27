<?php

namespace service;

use Exception;
use helper\mapper\ConfigurationMapper;
use model\configuration\Configuration;
use model\configuration\LogFile;

class ConfigurationService {
    static private string $CONFIGURATION_FILE = __DIR__ . '/../configuration.json';
    static private Configuration $CONFIGURATION;
    static private int $CONFIGURATION_FILE_LAST_CHANGED = 0;
    static private array $CONFIGURATION_FILE_LAST_CHANGED_CLASS_MAP = [];

    private function __construct() {}

    static public function get(string $attribute, string $className): mixed {
        self::loadEntitiesWhenNecessary();
        LogService::info(LogFile::CONFIGURATION, "Load attribute '$attribute'");
        try {
            self::$CONFIGURATION_FILE_LAST_CHANGED_CLASS_MAP[$className] = self::$CONFIGURATION_FILE_LAST_CHANGED;
            return self::$CONFIGURATION->{"$attribute"};
        } catch (Exception $exception) {
            LogService::error(LogFile::CONFIGURATION, "Could not load attribute '$attribute'", $exception);
            return NULL;
        }
    }
    static public function hasChanges(string $className): bool {
        self::loadEntitiesWhenNecessary();
        return self::$CONFIGURATION_FILE_LAST_CHANGED_CLASS_MAP[$className] != self::$CONFIGURATION_FILE_LAST_CHANGED;
    }

    static private function loadEntitiesWhenNecessary(): void {
        if (self::hasConfigurationFileChanged())
            self::load();
    }

    static private function hasConfigurationFileChanged(): bool {
        $configurationFileLastChanged = self::$CONFIGURATION_FILE_LAST_CHANGED;
        self::$CONFIGURATION_FILE_LAST_CHANGED = filemtime(self::$CONFIGURATION_FILE);
        return $configurationFileLastChanged != self::$CONFIGURATION_FILE_LAST_CHANGED;
    }

    static private function load(): void {
        LogService::info(LogFile::CONFIGURATION, 'Load configuration');
        self::$CONFIGURATION = FileService::parseFile(self::$CONFIGURATION_FILE, new ConfigurationMapper());
    }
}