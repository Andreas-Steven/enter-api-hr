<?php

declare(strict_types=1);

namespace App\Application\Attendance\Dto;

use App\Domain\Attendance\ValueObject\AttendanceBlacklist;

final readonly class AttendanceDetailInfo
{
    /**
     * @param AttendanceBlacklist[] $blackList
     */
    public function __construct(
        public array $blackList = [],
    ) {}

    public function toArray(): array 
    {
        return [
            // Kembalikan VO menjadi array mentah untuk JSON DB
            'blacklists' => array_map(
                fn(AttendanceBlacklist $vo) => $vo->toArray(),
                $this->blackList
            ),
        ];
    }
}