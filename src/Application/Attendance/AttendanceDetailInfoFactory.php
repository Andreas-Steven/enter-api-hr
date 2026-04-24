<?php

declare(strict_types=1);

namespace App\Application\Attendance;

use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

// Application DTO
use App\Application\Attendance\Dto\AttendanceDetailInfo;

final readonly class AttendanceDetailInfoFactory
{
    public function __construct() {}

    public function buildFromPrimitives(
        ?bool $dayoff = false,
        ?array $blacklist = [],

    ): AttendanceDetailInfo {
        return new AttendanceDetailInfo(
            dayoff: $dayoff,
            blacklist: $blacklist,
        );
    }
}