<?php

declare(strict_types=1);

namespace App\Domain\PayrollReport\ValueObject;

// Shared Layer
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

/**
 * PayrollReport Period Value Object.
 *
 * A period is an 8-digit integer YYYYMM. The payroll period mirrors the
 * attendance period: it spans from the START_DAY of the previous month up to
 * the CUTOFF_DAY of the period month, e.g. period 202608 => 2026-07-26 .. 2026-08-25.
 */
final readonly class Period
{
    private ?int $value;

    private function __construct(?int $value)
    {
        $this->value = $value;
    }

    public static function fromNullable(?int $value): self
    {
        if ($value === null) {
            return new self(null);
        }

        $year = intdiv($value, 100);
        $month = $value % 100;

        if ($month < 1 || $month > 12 || $year < 1900 || $year > 9999) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'payroll_report.period.invalid',
                    domain: 'app',
                    params: ['value' => $value],
                ),
            );
        }

        return new self($value);
    }

    public function toInt(): ?int
    {
        return $this->value;
    }

    public function isNull(): bool
    {
        return $this->value === null;
    }
}
