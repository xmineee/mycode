<?php

declare(strict_types=1);

namespace app\core;

use app\exceptions\FileException;

class JsonConfigParser
{
    private static array $config = [];

    public static function load(string $configFile = 'config.json'): void
    {
        $filename = PROJECT_ROOT . $configFile;

        if (!file_exists($filename)) {
            throw new FileException("Configuration file not found: $filename");
        }

        $jsonContent = file_get_contents($filename);
        if ($jsonContent === false) {
            throw new FileException("Cannot read configuration file: $filename");
        }

        $config = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new FileException("Invalid JSON in configuration file: " . json_last_error_msg());
        }

        self::$config = $config;
        self::setEnvironmentVariables($config);
    }

    private static function setEnvironmentVariables(array $config): void
    {
        self::setEnvFromArray($config);
    }

    private static function setEnvFromArray(array $array, string $prefix = ''): void
    {
        foreach ($array as $key => $value) {
            $envKey = $prefix ? $prefix . '_' . strtoupper($key) : strtoupper($key);

            if (is_array($value)) {
                self::setEnvFromArray($value, $envKey);
            } else {
                $_ENV[$envKey] = $value;
                $_SERVER[$envKey] = $value;
                putenv("$envKey=$value");
            }
        }
    }

    public static function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public static function getAll(): array
    {
        return self::$config;
    }

    public static function has(string $key): bool
    {
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return false;
            }
            $value = $value[$k];
        }

        return true;
    }
}