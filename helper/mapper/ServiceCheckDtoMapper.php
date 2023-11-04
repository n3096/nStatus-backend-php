<?php

namespace helper\mapper;

use model\DateTimeSerializable;
use model\dto\ServiceCheckDto;
use model\Status;
use stdClass;

class ServiceCheckDtoMapper {
    public function __invoke(stdClass $object): ServiceCheckDto {
        return new ServiceCheckDto(DateTimeSerializable::parse($object->timestamp), $object->latency, Status::parse($object->status));
    }

    public static function map(): callable {
        return new ServiceCheckDtoMapper();
    }
}