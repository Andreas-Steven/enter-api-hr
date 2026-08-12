<?php

declare(strict_types=1);

namespace App\Domain\PayrollReports\ValueObject;

/**
 * Value object representing the generated display name of a payroll report.
 *
 * Format: PAYROLL-{attendanceSummaryId}-{period}
 */
final readonly class Name
{
    private function __construct(
        private string $value
    ) {}

    public static function fromAttendanceSummaryAndPeriod(int $attendanceSummaryId, int $period): self
    {
        return new self(value: sprintf('PAYROLL-%d-%d', $attendanceSummaryId, $period));
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
