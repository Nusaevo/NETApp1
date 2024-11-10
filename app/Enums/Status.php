<?php

namespace App\Enums;

class Status
{
    const PENDING = 'PND';
    const APPROVED = 'APPR';
    const REJECTED = 'RJCT';
    const ACTIVE = 'A';
    const NONACTIVE = 'N';
    const IN_PROGRESS = 'IN_PRG';
    const COMPLETED = 'CMPLT';
    const CANCELED = 'CNCL';
    const ARCHIVED = 'ARCH';
    const ON_HOLD = 'ON_HLD';
    const DRAFT = 'D';
    const OPEN = 'O';
    const VOID = 'V';
    const POSTED = 'P';
    const SUCCESS = 'S';
    const ERROR = 'E';

    /**
     * Get the full status string from the abbreviation.
     *
     * @param string $abbreviation
     * @return string
     */
    public static function getStatusString($abbreviation)
    {
        $statuses = [
            self::PENDING => 'PENDING',
            self::APPROVED => 'APPROVED',
            self::REJECTED => 'REJECTED',
            self::ACTIVE => 'ACTIVE',
            self::NONACTIVE  => 'NON ACTIVE',
            self::IN_PROGRESS => 'IN_PROGRESS',
            self::COMPLETED => 'COMPLETED',
            self::CANCELED => 'CANCELED',
            self::ARCHIVED => 'ARCHIVED',
            self::ON_HOLD => 'ON_HOLD',
            self::DRAFT => 'DRAFT',
            self::OPEN => 'OPEN',
            self::VOID => 'VOID',
            self::POSTED => 'POSTED',
            self::SUCCESS => 'SUCCESS',
            self::ERROR => 'ERROR',
        ];

        return $statuses[$abbreviation] ?? 'Unknown Status';
    }
}
