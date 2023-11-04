<?php

namespace service;

use helper\mapper\ConfigurationMapper;
use model\configuration\Configuration;
use Throwable;

class ConfigurationService {
    private static string $CONFIGURATION_FILE = __DIR__ . '/../configuration.json';
    private static Configuration $CONFIGURATION;
    private static int $CONFIGURATION_FILE_LAST_CHANGED = 0;
    private static array $CONFIGURATION_FILE_LAST_CHANGED_CLASS_MAP = [];
    private static string $DEBUG_MODE_KEY = 'isDebug';

    private function __construct() {}

    public static function get(string $attribute, string $className): mixed {
        self::loadEntitiesWhenNecessary();

        if ($attribute !== self::$DEBUG_MODE_KEY)
            LogService::debug("Load attribute '$attribute'");

        try {
            self::$CONFIGURATION_FILE_LAST_CHANGED_CLASS_MAP[$className] = self::$CONFIGURATION_FILE_LAST_CHANGED;
            return self::$CONFIGURATION->{"$attribute"};
        } catch (Throwable $throwable) {
            LogService::error("Could not load attribute '$attribute'", $throwable);
            return NULL;
        }
    }
    public static function hasChanges(string $className): bool {
        self::loadEntitiesWhenNecessary();
        return self::$CONFIGURATION_FILE_LAST_CHANGED_CLASS_MAP[$className] != self::$CONFIGURATION_FILE_LAST_CHANGED;
    }

    private static function loadEntitiesWhenNecessary(): void {
        if (self::hasConfigurationFileChanged())
            self::load();
    }

    private static function hasConfigurationFileChanged(): bool {
        $configurationFileLastChanged = self::$CONFIGURATION_FILE_LAST_CHANGED;
        self::$CONFIGURATION_FILE_LAST_CHANGED = filemtime(self::$CONFIGURATION_FILE);
        return $configurationFileLastChanged != self::$CONFIGURATION_FILE_LAST_CHANGED;
    }

    private static function load(): void {
        LogService::info('Load configuration');
        self::$CONFIGURATION = FileService::parseFile(self::$CONFIGURATION_FILE, new ConfigurationMapper());
        LogService::info("DEBUG MODE " . (self::$CONFIGURATION->{self::$DEBUG_MODE_KEY} ? 'enabled' : 'disabled'));
    }
}