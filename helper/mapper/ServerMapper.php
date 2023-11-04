<?php

namespace helper\mapper;

use InvalidArgumentException;
use model\Server;
use service\LogService;
use stdClass;
use Throwable;

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
            } catch (Throwable $throwable) {
                LogService::error("Could not map to Service in Server with id '$object->id' on array key '$key'", $throwable);
            }
        }
        return new Server($object->id, $object->name, $services);
    }

    public static function map(): callable {
        return new ServerMapper();
    }
}