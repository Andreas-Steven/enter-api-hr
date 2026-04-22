<?php

declare(strict_types=1);

namespace App\Domain\Attendance\ValueObject;

use InvalidArgumentException;

final class AttendancePeriod
{
    private int $value;

    private function __construct(int $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    public static function fromDate(string $date, int $lastDateLimit = 21): self
    {
        $dateTime = new \DateTime($date);
        $day = (int) $dateTime->format('d');

        if ($day >= $lastDateLimit) {
            $dateTime->modify('first day of next month');
        }

        return new self((int) $dateTime->format('Ym'));
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    private function validate(int $value): void
    {
        // Validasi simpel: harus 6 digit
        if ($value < 100000 || $value > 999999) {
            throw new InvalidArgumentException("Invalid period format. Expected YYYYMM.");
        }
    }

    public function value(): int
    {
        return $this->value;
    }
}