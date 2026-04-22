<?php

declare(strict_types=1);

namespace App\Domain\Attendance\ValueObject;

use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

final class Blacklist
{
    private function __construct(
        private readonly int $employeeId,
        private readonly string $employeeName,
        private readonly string $date,
        private readonly string $startTime,
        private readonly string $actualTime,
    ) {}

    public static function create(
        int $employeeId,
        string $employeeName,
        string $date,
        string $startTime,
    ): self {
        return new self(
            employeeId: $employeeId,
            employeeName: $employeeName,
            date: $date,
            startTime: $startTime,
            actualTime: date('H:i:s'),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            employeeId: (int) ($data['employee_id'] ?? 0),
            employeeName: (string) ($data['employee_name'] ?? ''),
            date: (string) ($data['date'] ?? ''),
            startTime: (string) ($data['start_time'] ?? ''),
            actualTime: (string) ($data['actual_time'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return [
            'employee_id' => $this->employeeId,
            'employee_name' => $this->employeeName,
            'date' => $this->date,
            'start_time' => $this->startTime,
            'actual_time' => $this->actualTime,
        ];
    }
}