<?php

namespace helper\mapper;

use InvalidArgumentException;
use model\configuration\LogFile;
use model\dto\ServiceDto;
use model\Status;
use service\LogService;
use stdClass;
use Throwable;

class ServiceDtoMapper {
    public function __invoke(stdClass $object): ServiceDto {
        if (!isset($object->serviceCheckHistory))
            throw new InvalidArgumentException('Attribute \'serviceCheckHistory\' missing');
        if (!is_array($object->serviceCheckHistory))
            throw new InvalidArgumentException(sprintf('Attribute \'serviceCheckHistory\' must be an array, but is \'%s\' instead.', gettype($object->serviceCheckHistory)));

        $serviceCheckHistory = [];
        foreach ($object->serviceCheckHistory as $key => $serviceCheckDto) {
            try {
                $serviceCheckHistory[] = call_user_func(ServiceCheckDtoMapper::map(), $serviceCheckDto);
            } catch (Throwable $throwable) {
                LogService::error(LogFile::MAPPING, "Could not map to ServiceCheckDto in Service with id '$object->id' on array key '$key'", $throwable);
            }
        }
        return new ServiceDto($object->id, $object->name, $object->icon, Status::parse($object->status), $serviceCheckHistory);
    }

    static public function map(): callable {
        return new ServiceDtoMapper();
    }
}