<?php

namespace model;

use DateTime;
use JsonSerializable;
use ReturnTypeWillChange;

class DateTimeSerializable extends DateTime implements JsonSerializable {
    #[ReturnTypeWillChange] public function jsonSerialize() {
        return $this->format("c");
    }
}
