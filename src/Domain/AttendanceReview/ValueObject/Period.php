<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReview\ValueObject;

// Shared Layer
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

/**
 * AttendanceReview Period Value Object.
 *
 * A period is an 8-digit integer YYYYMM. The review period mirrors the
 * AttendanceSummary period: one review per employee per period.
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
                    key: 'attendance_review.period.invalid',
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
