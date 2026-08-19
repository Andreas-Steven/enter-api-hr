<?php

declare(strict_types=1);

namespace App\Domain\AttendanceSummary\ValueObject;

// Shared Layer
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

/**
 * AttendanceSummary Name Value Object.
 *
 * Validates the auto-generated summary name (e.g. "ATT-SUM-{period}-{employeeId}").
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
                    params: ['resource' => 'AttendanceSummary'],
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
                        'resource' => 'AttendanceSummary',
                        'min' => self::MIN_LENGTH,
                        'max' => self::MAX_LENGTH,
                    ],
                ),
            );
        }

        return new self($value);
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
