<?php

namespace helper\mapper;

use Exception;
use InvalidArgumentException;
use model\configuration\Configuration;
use model\configuration\LogFile;
use service\LogService;
use stdClass;

class ConfigurationMapper {
    public function __invoke(stdClass $object): Configuration {
        if (!isset($object->servers))
            throw new InvalidArgumentException('Argument Server not given');
        if (!is_array($object->servers))
            throw new InvalidArgumentException(sprintf('Argument Server must be an array. %s given.', gettype($object->servers)));

        $servers = [];
        foreach ($object->servers as $key => $server) {
            try {
                $servers[] = call_user_func(new ServerMapper(), $server);
            } catch (Exception $exception) {
                LogService::error(LogFile::MAPPING, "Could not map to Server on array key '$key'", $exception);
            }
        }
        return new Configuration($servers, (array)$object->basePaths);
    }

    static public function map(): callable {
        return new ConfigurationMapper();
    }
}