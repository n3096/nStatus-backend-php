<?php

namespace model;

use InvalidArgumentException;
use JsonSerializable;
use ReturnTypeWillChange;

enum Status: string implements JsonSerializable {
    case REACHABLE = "REACHABLE";
    case WARNING = "WARNING";
    case UNREACHABLE = "UNREACHABLE";
    case UNKNOWN = "UNKNOWN";

    #[ReturnTypeWillChange] public function jsonSerialize(): string {
        return $this->value;
    }

    public static function parse(?string $status): Status {
        return match ($status) {
            self::REACHABLE->value => self::REACHABLE,
            self::WARNING->value => self::WARNING,
            self::UNREACHABLE->value => self::UNREACHABLE,
            self::UNKNOWN->value => self::UNKNOWN,
            default => throw new InvalidArgumentException("Invalid Status: '$status'"),
        };
    }
}