<?php

/*
**	@author:	Philipp Wilke
**	@version: 	1.0.0
**	@last edit:	24.10.2023
**
**	@infos:	This object is for secure CURL data exchange with one of any object.
**          Inspired by: https://naveensnayak.wordpress.com/2013/03/12/simple-php-encrypt-and-decrypt/
*/

namespace model\security;
class nSecure
{
    private static string $FIXED_SALT = '!@6G8A7$^K32z*^p#x@eu%@6$Wi2Vja6$3fJCN^%p*3!$R4Xtb3Q#4rN5^3t#KU^&aYhSHh!Ms^%8cTyETw%c6PvL##izw$Ke8pBmo%#6!B^M*c3';
    private static string $ENCRYPT_METHOD = "AES-256-CBC";
    private static string $SECRET_KEY = '*#y&zjp3*#XFwQ3crBEJokU#A&@SEUr&eMrKf@hPE7H%VB8N@uZZ#D$ao%A^^JV8Ufy!BSD2jG6AYFGLHbFX9QAQN*DM^g$#swx!Dv8%H4qTAco^rMzLCj#CofVU5eYT';
    private static string $SECRET_IV = '*ATD7^@N$zaA6njXnj7f*^8pwg@ScHPhwp%q3zu4q*RmgsY!^RuDjXNHS&38LZVZSqBKZ2MXomu$4&kJiKg5J&m5SHpoP$AR&AzUnAHdTCwkXdf5!BdchHjcyd&B&Fx*';

    public static function encrypt(mixed $data, string $password, array $salts): string {
        $dataString = json_encode($data);
        $hash = self::nHash($password, $salts);

        $actualEncryptedString = openssl_encrypt($dataString, self::$ENCRYPT_METHOD, self::getKey($hash, $salts), 0, self::getIv($hash, $salts));
        return urlencode(base64_encode($actualEncryptedString));
    }

    public static function decrypt(string $string, string $password, array $salts): mixed {
        $hash = self::nHash($password, $salts);
        $actualEncryptedString = base64_decode(urldecode($string));
        $decryptedString = openssl_decrypt($actualEncryptedString, self::$ENCRYPT_METHOD, self::getKey($hash, $salts), 0, self::getIv($hash, $salts));
        return json_decode($decryptedString, TRUE);
    }

    public static function generateSaltList(): array {
        $hashList = [];
        $hashListLength = rand(5,9);

        for ($i = 0; $i < $hashListLength; $i++) {
            $hashList[] = self::generateRandomString(rand(101, 127));
        }
        return $hashList;
    }

    private static function generateRandomString(int $length): string {
        $characters = '0123456789abcdefghilkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+=-';
        $charLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charLength - 1)];
        }
        return $randomString;
    }

    private static function getKey(string $hash, array $salts): string {
        return hash('sha256', self::nHash(self::$SECRET_KEY . $hash, $salts));
    }

    private static function getIv(string $hash, array $salts): string {
        $string32Bit = md5(substr(hash('sha256', self::nHash($hash . self::$SECRET_IV, $salts)), 0, 16));
        return implode('', array_filter(str_split($string32Bit), function($key) { return $key % 2 == 0; }, ARRAY_FILTER_USE_KEY));
    }

    private static function nHash(string $string, array $salts): string {
        $string = base64_encode(hash('sha256', md5($string) . self::$FIXED_SALT));
        foreach ($salts as $salt) {
            $string = md5(hash('sha256', $string . self::$FIXED_SALT) . $salt);
        }
        return $string;
    }
}
