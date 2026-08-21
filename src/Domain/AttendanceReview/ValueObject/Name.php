<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReview\ValueObject;

// Shared Layer
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

/**
 * AttendanceReview Name Value Object.
 *
 * Validates the auto-generated review name. Each evidence case gets its own
 * review record with a unique name:
 *   REVIEW-EK{employeeId}-{period}-{evidenceKey}
 *   e.g. REVIEW-EK4-202608-D2026-08-10-missing_out
 *        REVIEW-EK4-202608-R1-deficit
 */
final readonly class Name
{
    public const MIN_LENGTH = 3;
    public const MAX_LENGTH = 255;

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        $value = trim($value);

        if ($value === '') {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'validation.name_required',
                    domain: 'validation',
                    params: ['resource' => 'AttendanceReview'],
                ),
            );
        }

        $length = strlen($value);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'validation.name_length',
                    domain: 'validation',
                    params: [
                        'resource' => 'AttendanceReview',
                        'min' => self::MIN_LENGTH,
                        'max' => self::MAX_LENGTH,
                    ],
                ),
            );
        }

        return new self($value);
    }

    /**
     * Builds the auto-generated name from the employee id, period and a
     * per-evidence unique key.
     *
     * Format: REVIEW-EK{employeeId}-{period}-{evidenceKey}
     *   e.g. REVIEW-EK4-202608-D2026-08-10-missing_out
     *        REVIEW-EK4-202608-R1-deficit
     */
    public static function fromEmployeePeriodAndKey(
        int $employeeId,
        int $period,
        string $evidenceKey,
    ): self {
        return self::fromString(
            sprintf('REVIEW-EK%d-%d-%s', $employeeId, $period, $evidenceKey)
        );
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
