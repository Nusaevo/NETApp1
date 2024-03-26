<?php

namespace App\Enums;

namespace App\Enums;

class Status
{
    const PENDING = 'PND';
    const APPROVED = 'APPR';
    const REJECTED = 'RJCT';
    const ACTIVE = 'A';
    const DEACTIVATED = 'N';
    const IN_PROGRESS = 'IN_PRG';
    const COMPLETED = 'CMPLT';
    const CANCELED = 'CNCL';
    const ARCHIVED = 'ARCH';
    const DRAFT = 'DRFT';
    const ON_HOLD = 'ON_HLD';

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
            self::DEACTIVATED  => 'DEACTIVATED',
            self::IN_PROGRESS => 'IN_PROGRESS',
            self::COMPLETED => 'COMPLETED',
            self::CANCELED => 'CANCELED',
            self::ARCHIVED => 'ARCHIVED',
            self::DRAFT => 'DRAFT',
            self::ON_HOLD => 'ON_HOLD',
        ];

        return $statuses[$abbreviation] ?? '';
    }
}
