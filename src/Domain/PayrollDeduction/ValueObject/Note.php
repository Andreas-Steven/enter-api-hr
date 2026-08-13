<?php

declare(strict_types=1);

namespace App\Domain\PayrollDeduction\ValueObject;

/**
 * Value Object untuk note PayrollDeduction.
 *
 * Jika note kosong/null, akan auto-generate berdasarkan context:
 * - fromNullable: "Auto-deducted via payroll {period}" (cascade saat aktivasi)
 * - fromNullableForDebtCreation: "Auto-created from EmployeeDebtTransaction for period {period}" (reverse flow)
 */
final readonly class Note
{
    private function __construct(
        private ?string $value
    ) {}

    /**
     * Buat Note dari nullable string. Jika null/empty, auto-generate:
     * "Auto-deducted via payroll {period}".
     *
     * Dipakai saat cascade pemotongan hutang dari PayrollDeduction -> EmployeeDebt.
     */
    public static function fromNullable(?string $note, int $period): self
    {
        if ($note === null || trim($note) === '') {
            return new self(sprintf('Auto-deducted via payroll %d', $period));
        }

        return new self($note);
    }

    /**
     * Buat Note dari nullable string. Jika null/empty, auto-generate:
     * "Auto-created from EmployeeDebtTransaction for period {period}".
     *
     * Dipakai saat auto-create PayrollDeduction dari EmployeeDebtTransaction (reverse flow).
     */
    public static function fromNullableForDebtCreation(?string $note, int $period): self
    {
        if ($note === null || trim($note) === '') {
            return new self(sprintf('Auto-created from EmployeeDebtTransaction for period %d', $period));
        }

        return new self($note);
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): ?string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
