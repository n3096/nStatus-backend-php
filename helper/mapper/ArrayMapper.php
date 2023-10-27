<?php

namespace helper\mapper;

use Exception;
use model\configuration\LogFile;
use service\LogService;

class ArrayMapper {
    public mixed $mapFunction;

    public function __construct(callable $mapFunction) {
        $this->mapFunction = $mapFunction;
    }

    public function __invoke(array $array): array {
        $result = [];
        foreach ($array as $key => $item) {
            try {
                $result[] = call_user_func($this->mapFunction, $item);
            } catch (Exception $exception) {
                LogService::error(LogFile::MAPPING, "Could not map to item on key '$key'", $exception);
            }
        }
        return $result;
    }

    static public function map(callable $mapFunction): callable {
        return new ArrayMapper($mapFunction);
    }
}