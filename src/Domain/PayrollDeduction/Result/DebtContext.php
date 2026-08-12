<?php

declare(strict_types=1);

namespace App\Domain\PayrollDeduction\Result;

/**
 * Hasil resolve context pembuatan PayrollDeduction.
 *
 * - Jika employeeDebtId null: potongan gaji biasa, employeeId &
 *   deductionTypeId berasal dari input.
 * - Jika employeeDebtId tidak null: potongan terkait EmployeeDebt,
 *   employeeId & deductionTypeId di-derive dari EmployeeDebt.
 */
final readonly class DebtContext
{
    public function __construct(
        public int $employeeId,
        public int $deductionTypeId,
        public ?int $employeeDebtId,
    ) {}

    public static function create(
        int $employeeId,
        int $deductionTypeId,
        ?int $employeeDebtId,
    ): self {
        return new self(
            employeeId: $employeeId,
            deductionTypeId: $deductionTypeId,
            employeeDebtId: $employeeDebtId,
        );
    }
}
