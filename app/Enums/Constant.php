<?php

namespace App\Enums;
use Illuminate\Support\Str;

class Constant
{
    public static function ConfigConn(): string
    {
        return "pgsql";
    }

    public static function AppConn(): string
    {
        return "main";
    }
}
