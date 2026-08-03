<?php

declare(strict_types=1);

namespace App\Domain\AttendancePeriod\ValueObject;

use App\Domain\Shared\Enums\AttendancePeriodConfig;
use DateTimeImmutable;

final readonly class PayoutDate
{
    private function __construct(
        private DateTimeImmutable $value
    ) {
    }

    public static function fromYearMonth(int $year, int $month): self
    {
        $date = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $date = $date->modify('last day of this month');

        return new self($date);
    }

    public static function fromDateTimeImmutable(DateTimeImmutable $dateTime): self
    {
        return new self($dateTime);
    }

    public static function fromJson(?string $json): ?self
    {
        if ($json === null || trim($json) === '') {
            return null;
        }

        $dateString = trim($json);

        try {
            return new self(new DateTimeImmutable($dateString));
        } catch (\Throwable) {
            return null;
        }
    }

    public function toDateTime(): DateTimeImmutable
    {
        return $this->value;
    }

    public function toString(): string
    {
        return $this->value->format('Y-m-d');
    }
}
