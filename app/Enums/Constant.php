<?php

namespace App\Enums;
use Illuminate\Support\Str;

class Constant
{
    public static function SysConfig1_ConnectionString(): string
    {
        return "pgsql";
    }

    public static function Trdjewel1_ConnectionString(): string
    {
        //return Str::lower(config('database.connections.trdjewel1.database'));
        return "trdjewel1";
    }

    public static function TrdRetail1_ConnectionString(): string
    {
        //return Str::lower(config('database.connections.trdjewel1.database'));
        return "trdretail1";
    }
}
