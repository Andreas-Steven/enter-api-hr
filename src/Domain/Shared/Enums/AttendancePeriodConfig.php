<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

final class AttendancePeriodConfig
{
    public const CUTOFF_DAY = 25;
    public const START_DAY = 26;

    /** @deprecated Use CUTOFF_DAY instead */
    public const LAST_DATE = self::CUTOFF_DAY;
}
