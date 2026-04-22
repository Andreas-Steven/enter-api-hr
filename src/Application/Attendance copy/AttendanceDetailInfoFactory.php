<?php

declare(strict_types=1);

namespace App\Application\Attendance;

use App\Application\Attendance\Dto\AttendanceDetailInfo;
use App\Domain\Attendance\ValueObject\AttendanceBlacklist;

final readonly class AttendanceDetailInfoFactory
{
    /**
     * @param array|null $blackList Array of raw blacklist data
     */
    public function buildFromPrimitives(
        ?array $blackList,
        ?bool $dayOff = false,
    ): AttendanceDetailInfo 
    {
        // Kita petakan array mentah menjadi Array of Value Objects
        $blacklistObjects = array_map(
            fn(array $item) => AttendanceBlacklist::fromArray($item),
            $blackList ?? []
        );

        return new AttendanceDetailInfo(
            blackList: $blacklistObjects,
            dayOff: $dayOff,
        );
    }
}