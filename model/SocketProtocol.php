<?php

namespace model;

use InvalidArgumentException;

enum SocketProtocol: string {
    case TCP = "TCP";
    case HTTP = "HTTP";
    case HTTPS = "HTTPS";
    case UDP = "UDP";

    public static function parse(string $socketProtocol) {
        switch ($socketProtocol) {
            case self::TCP->value:
                return self::TCP;
            case self::HTTP->value:
                return self::HTTP;
            case self::HTTPS->value:
                return self::HTTPS;
            case self::UDP->value:
                return self::UDP;
            default:
                throw new InvalidArgumentException("Invalid SocketProtocol: '$socketProtocol'");
        }
    }
}