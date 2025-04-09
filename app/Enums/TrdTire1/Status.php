<?php

namespace App\Enums\TrdTire1;

class Status
{
    const OPEN = 'O';
    const PRINT = 'P';
    const CANCEL = 'x';
    const SHIP = 'S';
    const ACTIVE = 'A';

    /**
     * Get the full status string from the abbreviation.
     *
     * @param string $abbreviation
     * @return string
     */
    public static function getStatusString($abbreviation)
    {
        $statuses = [
            self::OPEN => 'OPEN',
            self::PRINT => 'PRINT',
            self::CANCEL => 'CANCEL',
            self::SHIP => 'SHIP',
            self::ACTIVE => 'ACTIVE',
        ];

        return $statuses[$abbreviation] ?? 'Unknown Status';
    }
}
