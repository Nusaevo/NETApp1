<?php

namespace App\Enums\TrdTire1;

class Status
{
    const REJECTED = 'J';
    const DEPOSIT = 'D';
    const SHIP = 'S';
    const BILL = 'T';
    const PAID = 'R';
    const OPEN = 'O';
    const COMPLETED = 'C';
    const ACTIVE = 'A';
    const NONACTIVE = 'N';
    const CANCEL = 'X';
    const PRINT = 'P';

    /**
     * Get the full status string from the abbreviation.
     *
     * @param string $abbreviation
     * @return string
     */
    public static function getStatusString($abbreviation)
    {
        $statuses = [
            self::REJECTED => 'REJECTED',
            self::DEPOSIT => 'DEPOSIT',
            self::BILL => 'BILL',
            self::OPEN => 'OPEN',
            self::PRINT => 'PRINT',
            self::CANCEL => 'CANCEL',
            self::SHIP => 'SHIP',
            self::ACTIVE => 'ACTIVE',
            self::NONACTIVE => 'NONACTIVE',
            self::PAID => 'PAID',
            self::COMPLETED => 'COMPLETED',

        ];

        return $statuses[$abbreviation] ?? 'Unknown Status';
    }
}
