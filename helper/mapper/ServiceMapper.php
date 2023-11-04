<?php

namespace helper\mapper;

use InvalidArgumentException;
use model\Service;
use model\SocketProtocol;
use stdClass;

class ServiceMapper {
    public function __invoke(stdClass $object): Service {
        return new Service( $object->id, $object->name, $object->hostName, SocketProtocol::parse($object->socketProtocol), $object->port, $object->icon, $object->enabled, $object->timeout);
    }

    public static function map(): callable {
        return new ServiceMapper();
    }
}