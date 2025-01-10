<?php

namespace App\Services;

class VersionStore
{
    protected static $versionNumbers = [];

    public static function getVersion($key)
    {
        return self::$versionNumbers[$key] ?? null;
    }

    public static function setVersion($key, $version)
    {
        self::$versionNumbers[$key] = $version;
    }

    public static function incrementVersion($key)
    {
        if (!isset(self::$versionNumbers[$key])) {
            self::$versionNumbers[$key] = 1;
        } else {
            self::$versionNumbers[$key]++;
        }

        return self::$versionNumbers[$key];
    }
}
