<?php

declare(strict_types=1);

namespace App\Application\PayrollDeduction\Command;

final readonly class CreatePayrollDeductionCommand
{
    public function __construct(
        public int $employeeId,
        public int $deductionTypeId,
        public ?int $attendanceReportId,
        public ?int $employeeDebtId,
        public ?int $employeeDebtInstallmentId,
        public int $period,
        public float $amount,
        public ?string $description,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        int $employeeId,
        int $deductionTypeId,
        int $period,
        float $amount,
        int $status,
        ?int $attendanceReportId = null,
        ?int $employeeDebtId = null,
        ?int $employeeDebtInstallmentId = null,
        ?string $description = null,
        ?array $detailInfo = null,
    ): self {
        return new self(
            employeeId: $employeeId,
            deductionTypeId: $deductionTypeId,
            attendanceReportId: $attendanceReportId,
            employeeDebtId: $employeeDebtId,
            employeeDebtInstallmentId: $employeeDebtInstallmentId,
            period: $period,
            amount: $amount,
            description: $description,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}