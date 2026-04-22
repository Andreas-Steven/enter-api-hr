<?php

declare(strict_types=1);

namespace App\Application\Attendance\Dto;

use App\Domain\Attendance\ValueObject\AttendanceItem;

final readonly class AttendanceShowRecord
{
    public function __construct(
        private AttendanceItem $item,
    ) {
    }

    public static function fromArray(array $record): self
    {
        return new self(
            item: AttendanceItem::fromArray($record)
        );
    }

    public function belongsToEmployee(int $employeeId): bool
    {
        return $this->item->employeeId === $employeeId;
    }

    public function toArray(): array
    {
        return $this->item->toArray();
    }
}
