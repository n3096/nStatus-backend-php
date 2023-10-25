<?php

namespace model;

use InvalidArgumentException;

enum SocketProtocol: string {
    case TCP = "TCP";
    case HTTP = "HTTP";
    case HTTPS = "HTTPS";
    case UDP = "UDP";

    public static function parse(string $socketProtocol): SocketProtocol {
        return match ($socketProtocol) {
            self::TCP->value => self::TCP,
            self::HTTP->value => self::HTTP,
            self::HTTPS->value => self::HTTPS,
            self::UDP->value => self::UDP,
            default => throw new InvalidArgumentException("Invalid SocketProtocol: '$socketProtocol'"),
        };
    }
}