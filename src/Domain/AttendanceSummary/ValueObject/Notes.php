<?php

declare(strict_types=1);

namespace App\Domain\AttendanceSummary\ValueObject;

/**
 * AttendanceSummary Notes Value Object.
 *
 * Optional free-form notes attached to a generated summary. Normalizes empty
 * input to null so the persistence layer can store a clean nullable value.
 */
final readonly class Notes
{
    private ?string $value;

    private function __construct(?string $value)
    {
        $this->value = $value;
    }

    public static function fromNullable(?string $value): self
    {
        if ($value === null) {
            return new self(null);
        }

        $value = trim($value);

        return new self($value === '' ? null : $value);
    }

    public function toString(): ?string
    {
        return $this->value;
    }
}
