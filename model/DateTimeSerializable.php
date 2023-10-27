<?php

namespace model;

use DateTime;
use Exception;
use InvalidArgumentException;
use JsonSerializable;
use ReturnTypeWillChange;

class DateTimeSerializable extends DateTime implements JsonSerializable {
    static public function parse(string $string): DateTimeSerializable {
        try {
            return new DateTimeSerializable($string);
        } catch (Exception) {
            throw new InvalidArgumentException("Could not parse from string '$string'");
        }
    }

    #[ReturnTypeWillChange] public function jsonSerialize() {
        return $this->format("c");
    }
}
