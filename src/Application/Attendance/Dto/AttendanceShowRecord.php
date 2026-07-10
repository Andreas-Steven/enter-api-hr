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
        $data = $this->item->toArray();
        unset($data['attendance_policies']);

        return $data;
    }

    public function matchesFilters(?string $name, ?int $divisionId): bool
    {
        $data = $this->toArray();
        
        // Cek Nama (Partial Search)
        if ($name !== null && stripos($data['employee_name'] ?? '', $name) === false) {
            return false;
        }

        // Cek Division ID (Deep Search)
        if ($divisionId !== null && ($data['division']['id'] ?? null) !== $divisionId) {
            return false;
        }

        return true;
    }
}