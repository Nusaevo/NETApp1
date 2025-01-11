<?php

namespace App\Enums;
use Illuminate\Support\Str;

class Constant
{
    public static function configConn(): string
    {
        return "pgsql";
    }

    public static function appConn(): string
    {
        return "main";
    }
}
