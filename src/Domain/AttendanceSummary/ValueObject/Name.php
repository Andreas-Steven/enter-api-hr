<?php

declare(strict_types=1);

namespace App\Domain\AttendanceSummary\ValueObject;

/**
 * Value object representing the generated display name of an attendance summary.
 *
 * Format: SUMMARY-EK{employeeId}-{period}
 */
final readonly class Name
{
    private function __construct(
        private string $value
    ) {}

    public static function fromEmployeeAndPeriod(int $employeeId, int $period): self
    {
        return new self(value: sprintf('SUMMARY-EK%d-%d', $employeeId, $period));
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
