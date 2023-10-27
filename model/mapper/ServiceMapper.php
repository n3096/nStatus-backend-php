<?php

namespace model\mapper;

use InvalidArgumentException;
use model\Service;
use model\SocketProtocol;
use stdClass;

class ServiceMapper {
    public function __invoke(stdClass $object): Service {
        $this->validateId($object->id);
        return new Service( $object->id, $object->name, $object->hostName, SocketProtocol::parse($object->socketProtocol), $object->port, $object->icon, $object->enabled, $object->timeout);
    }

    static public function map(): callable {
        return new ServiceMapper();
    }

    private function validateId(string $uuid): void {
        $uuidPattern = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/';
        if (preg_match($uuidPattern, $uuid) !== 1)
            throw new InvalidArgumentException("Attribute 'id' is not a valid UUID '$uuid'");
    }
}