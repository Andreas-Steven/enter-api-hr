<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReport\ValueObject;

/**
 * Value object representing the generated display name of an attendance report.
 */
final readonly class Name
{
    private function __construct(
        private string $value
    ) {}

    public static function fromEmployeeAndPeriod(string $employeeName, int $employeeId, int $period): self
    {
        // return new self(value: sprintf('%s - %d - %d', $employeeName, $employeeId, $period));
        return new self(value: sprintf('%s - EK%d - %d', $employeeName, $employeeId, $period));
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
