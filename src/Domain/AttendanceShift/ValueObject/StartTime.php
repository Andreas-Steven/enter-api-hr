<?php

declare(strict_types=1);

namespace App\Domain\AttendanceShift\ValueObject;

use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;
use DateTimeImmutable;

/**
 * Start Time Value Object
 *
 * Validates and stores a time-of-day value in HH:MM:SS format.
 */
final readonly class StartTime
{
    private function __construct(
        private string $value,
    ) {
        $this->validate($value);
    }

    public static function fromString(string $value): self
    {
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

    /**
     * @return array{hour: int, minute: int, second: int}
     */
    public function toParts(): array
    {
        $parts = explode(':', $this->value);

        return [
            'hour' => (int) $parts[0],
            'minute' => (int) $parts[1],
            'second' => isset($parts[2]) ? (int) $parts[2] : 0,
        ];
    }

    public function toMinutes(): int
    {
        $parts = $this->toParts();

        return $parts['hour'] * 60 + $parts['minute'];
    }

    private function validate(string $value): void
    {
        if (trim($value) === '') {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'required',
                    params: [
                        'resource' => 'start_time',
                    ],
                ),
            );
        }

        $time = DateTimeImmutable::createFromFormat('H:i:s', $value);

        if (!$time || $time->format('H:i:s') !== $value) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'time.format',
                    params: [
                        'field' => 'start_time',
                        'format' => 'HH:MM:SS',
                    ],
                    domain: 'validation',
                ),
            );
        }
    }
}
