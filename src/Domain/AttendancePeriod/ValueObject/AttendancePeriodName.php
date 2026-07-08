<?php

declare(strict_types=1);

namespace App\Domain\AttendancePeriod\ValueObject;

use DateTimeImmutable;

final readonly class AttendancePeriodName
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromYearMonth(int $year, int $month): self
    {
        // Start date is 26th of previous month
        $startDate = new DateTimeImmutable(sprintf('%d-%02d-26', $year, $month));
        $startDate = $startDate->modify('-1 month');

        // End date is 25th of current month
        $endDate = new DateTimeImmutable(sprintf('%d-%02d-25', $year, $month));

        $name = $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y');

        return new self($name);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
