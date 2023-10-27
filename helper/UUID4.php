<?php

namespace helper;

use Exception;
use InvalidArgumentException;

class UUID4 {
    private function __construct () {}

    static public function generate(): string|FALSE {
        try {
            $data = random_bytes(16);

            // Set the version (4) and reserved bits
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

            // Format the UUID
            return self::validate(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)));
        } catch (Exception) {return FALSE;}
    }

    static public function validate(string $string): string {
        if (!self::isValid($string))
            throw new InvalidArgumentException("Attribute 'id' is not a valid UUID '$string'");
        return $string;
    }

    static public function isValid(string $string): bool {
        $uuidPattern = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-4[0-9a-fA-F]{3}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/';
        return preg_match($uuidPattern, $string) === 1;
    }
}