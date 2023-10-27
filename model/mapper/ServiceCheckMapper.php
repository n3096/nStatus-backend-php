<?php

namespace model\mapper;

use model\DateTimeSerializable;
use model\ServiceCheck;
use model\SocketProtocol;
use model\Status;
use stdClass;

class ServiceCheckMapper {
    public function __invoke(stdClass $object): ServiceCheck {
        return new ServiceCheck($object->hostName, $object->port, SocketProtocol::parse($object->socketProtocol), $object->fullHostName, DateTimeSerializable::parse($object->timestamp), $object->latency, $object->ipv4, $object->ipv6, $object->forwardedHost, Status::parse($object->status), $object->response, $object->notes);
    }

    static public function map(): callable {
        return new ServiceCheckMapper();
    }
}