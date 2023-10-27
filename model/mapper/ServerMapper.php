<?php

namespace model\mapper;

use Exception;
use InvalidArgumentException;
use model\configuration\LogFile;
use model\Server;
use service\LogService;
use stdClass;

class ServerMapper {
    public function __invoke(stdClass $object): Server {
        if (!isset($object->services))
            throw new InvalidArgumentException('Attribute \'services\' missing');
        if (!is_array($object->services))
            throw new InvalidArgumentException(sprintf('Attribute \'services\' must be an array, but is \'%s\' instead.', gettype($object->services)));

        $services = [];
        foreach ($object->services as $key => $service) {
            try {
                $services[] = call_user_func(ServiceMapper::map(), $service);
            } catch (Exception) {
                LogService::error(LogFile::MAPPING, "Could not map to Service in server with id '$object->id' on array key '$key'");
            }
        }
        return new Server($object->id, $object->name, $services);
    }

    static public function map(): callable {
        return new ServerMapper();
    }
}