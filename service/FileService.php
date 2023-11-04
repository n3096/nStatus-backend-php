<?php

namespace service;

use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;

class FileService {
    private static int $DEFAULT_FILE_PERMISSION = 0777;
    private function __construct() {}

    public static function getSize(string $path): int|FALSE {
        if (is_file($path))
            return filesize($path);
        if (!is_dir($path))
            return FALSE;

        $totalSize = 0;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file) {
            if (is_file($file))
                $totalSize += filesize($file);
        }
        return $totalSize;
    }

    public static function parseFile(string $filePath, callable|FALSE $mapFunction = FALSE): mixed {
        $map = json_decode(self::read($filePath));
        if ($mapFunction) {
            try {
                return call_user_func($mapFunction, $map);
            } catch (Throwable $exception) {
                LogService::error("Could not parse from file '$filePath'", $exception);
                return FALSE;
            }
        }
        return $map;
    }

    public static function read(string $filePath): string {
        return file_get_contents($filePath);
    }

    public static function exists(string $filePath): bool {
        return file_exists($filePath);
    }

    public static function append(string $filePath, mixed $content): void {
        if (!is_string($content))
            $content = json_encode($content);
        self::write($filePath, "a", "$content\n");
    }

    public static function set(string $filePath, mixed $content): void {
        self::write($filePath, "w", $content);
    }

    public static function clear(string $filePath): void {
        self::set($filePath, "");
    }

    private static function write(string $filePath, string $mode, mixed $content): void {
        self::createDirectoryIfNotExists(dirname($filePath));

        if (!$fileHandler = fopen($filePath, $mode)) {
            LogService::error("Could not access file '$filePath");
            throw new RuntimeException("Could not open file '$filePath'");
        }
        self::requireLockFile($fileHandler);

        if (!is_string($content))
            $content = json_encode($content);
        fwrite($fileHandler, "$content");
        flock($fileHandler, LOCK_UN);
        fclose($fileHandler);
    }

    private static function createDirectoryIfNotExists(string $directoryPath): void {
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, self::$DEFAULT_FILE_PERMISSION, true);
        }
    }

    private static function requireLockFile($resource): void {
        if (!is_resource($resource))
            throw new InvalidArgumentException(sprintf('Argument must be a valid resource type. %s given.', gettype($resource)));

        $tries = 0;
        while ($tries < 10) {
            if (flock($resource, LOCK_EX))
                return;
            $tries++;
            usleep(500_000 );
        }
        $filePath = stream_get_meta_data($resource)['uri'];
        throw new RuntimeException("Could not acquire lock on ressource '$filePath'");
    }
}