<?php

namespace model;

use DateTime;
use InvalidArgumentException;
use JsonSerializable;
use ReturnTypeWillChange;
use Throwable;

class DateTimeSerializable extends DateTime implements JsonSerializable {
    static public function parse(string $string): DateTimeSerializable {
        try {
            return new DateTimeSerializable($string);
        } catch (Throwable) {
            throw new InvalidArgumentException("Could not parse from string '$string'");
        }
    }

    #[ReturnTypeWillChange] public function jsonSerialize() {
        return $this->format("c");
    }

    public function __invoke(string $string): DateTimeSerializable {
        return DateTimeSerializable::parse($string);
    }
}
