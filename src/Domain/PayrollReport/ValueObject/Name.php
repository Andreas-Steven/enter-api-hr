<?php

declare(strict_types=1);

namespace App\Domain\PayrollReport\ValueObject;

// Shared Layer
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

/**
 * PayrollReport Name Value Object.
 *
 * Validates the auto-generated payroll report name (e.g. "PAYROLL-{id}-{period}").
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
                    params: ['resource' => 'PayrollReport'],
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
                        'resource' => 'PayrollReport',
                        'min' => self::MIN_LENGTH,
                        'max' => self::MAX_LENGTH,
                    ],
                ),
            );
        }

        return new self($value);
    }

    /**
     * Builds the auto-generated name from the payroll report id and period.
     *
     * Format: PAYROLL-{id}-{period}  e.g. PAYROLL-5-202608
     */
    public static function fromIdAndPeriod(int $id, int $period): self
    {
        return self::fromString(sprintf('PAYROLL-%d-%d', $id, $period));
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
