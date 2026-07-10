<?php

declare(strict_types=1);

namespace App\Domain\AttendancePeriod\ValueObject;

use App\Domain\Shared\Enums\AttendancePeriodConfig;
use DateTimeImmutable;

final readonly class StartDate
{
    private function __construct(
        private DateTimeImmutable $value
    ) {
    }

    public static function fromYearMonth(int $year, int $month): self
    {
        // Start date is 26th of previous month
        $date = new DateTimeImmutable(sprintf('%d-%d-26', $year, $month));
        $date = $date->modify('-1 month');

        return new self($date);
    }

    public static function fromDateTimeImmutable(DateTimeImmutable $dateTime): self
    {
        return new self($dateTime);
    }

    public static function fromDateTime(DateTimeImmutable $dateTime): self
    {
        // If date >= 25, it belongs to next month's period
        if ((int) $dateTime->format('d') >= AttendancePeriodConfig::LAST_DATE) {
            $dateTime = $dateTime
                ->setDate(
                    (int) $dateTime->format('Y'),
                    (int) $dateTime->format('m'),
                    1
                )
                ->modify('+1 month');
        }

        // Start date is 26th of previous month
        $startDate = $dateTime
            ->setDate(
                (int) $dateTime->format('Y'),
                (int) $dateTime->format('m'),
                26
            )
            ->modify('-1 month');

        return new self($startDate);
    }

    public static function fromJson(?string $json): ?self
    {
        if ($json === null || trim($json) === '') {
            return null;
        }

        $dateString = self::extractDateString($json);

        if ($dateString === null) {
            return null;
        }

        try {
            return new self(new DateTimeImmutable($dateString));
        } catch (\Throwable) {
            return null;
        }
    }

    private static function extractDateString(string $json): ?string
    {
        $decoded = json_decode($json, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_array($decoded) && isset($decoded['date']) && is_string($decoded['date'])) {
                $decodedDate = trim($decoded['date']);
                return $decodedDate === '' ? null : $decodedDate;
            }

            if (is_string($decoded)) {
                $decodedDate = trim($decoded);
                return $decodedDate === '' ? null : $decodedDate;
            }

            return null;
        }

        $json = trim($json);

        return $json === '' ? null : $json;
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
