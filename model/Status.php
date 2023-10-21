<?php

namespace model;

use JsonSerializable;
use ReturnTypeWillChange;

enum Status: string implements JsonSerializable {
    case REACHABLE = "REACHABLE";
    case WARNING = "WARNING";
    case UNREACHABLE = "UNREACHABLE";

    #[ReturnTypeWillChange] public function jsonSerialize(): string
    {
        return $this->value;
    }
}