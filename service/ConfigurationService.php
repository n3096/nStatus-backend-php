<?php

namespace service;

use helper\mapper\ConfigurationMapper;
use model\configuration\Configuration;
use model\configuration\LogFile;
use Throwable;

class ConfigurationService {
    static private string $CONFIGURATION_FILE = __DIR__ . '/../configuration.json';
    static private Configuration $CONFIGURATION;
    static private int $CONFIGURATION_FILE_LAST_CHANGED = 0;
    static private array $CONFIGURATION_FILE_LAST_CHANGED_CLASS_MAP = [];
    static private string $DEBUG_MODE_KEY = 'isDebug';

    private function __construct() {}

    static public function get(string $attribute, string $className): mixed {
        self::loadEntitiesWhenNecessary();

        if ($attribute !== self::$DEBUG_MODE_KEY)
            LogService::debug(LogFile::CONFIGURATION, "Load attribute '$attribute'");

        try {
            self::$CONFIGURATION_FILE_LAST_CHANGED_CLASS_MAP[$className] = self::$CONFIGURATION_FILE_LAST_CHANGED;
            return self::$CONFIGURATION->{"$attribute"};
        } catch (Throwable $throwable) {
            LogService::error(LogFile::CONFIGURATION, "Could not load attribute '$attribute'", $throwable);
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
        LogService::info(LogFile::CONFIGURATION, "DEBUG MODE " . (self::$CONFIGURATION->{self::$DEBUG_MODE_KEY} ? 'enabled' : 'disabled'));
    }
}