<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebtInstallment;

use App\Application\EmployeeDebtInstallment\Dto\EmployeeDebtInstallmentDetailInfo;
use App\Domain\EmployeeDebt\Repository\EmployeeDebtRepositoryInterface;
use App\Domain\PayrollDeduction\Repository\PayrollDeductionRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * Membangun EmployeeDebtInstallmentDetailInfo dari ID relasi (employee_debt_id, payroll_deduction_id).
 *
 * Mengikuti pola EmployeeDebtDetailInfoFactory: lookup relasi via repository,
 * throw NotFoundException bila relasi tidak ditemukan, kembalikan DTO ringkas
 * untuk disimpan di kolom detail_info JSON.
 */
final readonly class EmployeeDebtInstallmentDetailInfoFactory
{
    public function __construct(
        private EmployeeDebtRepositoryInterface $employeeDebtRepository,
        private PayrollDeductionRepositoryInterface $payrollDeductionRepository,
    ) {}

    public function buildFromIds(int $employeeDebtId, ?int $payrollDeductionId = null): EmployeeDebtInstallmentDetailInfo
    {
        $debt = $this->employeeDebtRepository->findById(
            id: $employeeDebtId,
            status: null
        );

        if ($debt === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => 'EmployeeDebt',
                        'field' => 'id',
                        'value' => $employeeDebtId,
                    ]
                )
            );
        }

        $payrollDeduction = null;
        if ($payrollDeductionId !== null) {
            $deduction = $this->payrollDeductionRepository->findById(
                id: $payrollDeductionId,
                status: null
            );

            if ($deduction === null) {
                throw new NotFoundException(
                    translate: Message::create(
                        key: 'resource.not_found',
                        params: [
                            'resource' => 'PayrollDeduction',
                            'field' => 'id',
                            'value' => $payrollDeductionId,
                        ]
                    )
                );
            }

            $payrollDeduction = [
                'id' => $deduction->getId(),
                'name' => $deduction->getName(),
                'period' => $deduction->getPeriod(),
            ];
        }

        return new EmployeeDebtInstallmentDetailInfo(
            employee_debt: [
                'id' => $debt->getId(),
                'name' => $debt->getName(),
            ],
            payroll_deduction: $payrollDeduction,
        );
    }

    public function buildFromPrimitives(
        ?array $employee_debt = null,
        ?array $payroll_deduction = null,
    ): EmployeeDebtInstallmentDetailInfo {
        return new EmployeeDebtInstallmentDetailInfo(
            employee_debt: $employee_debt,
            payroll_deduction: $payroll_deduction,
        );
    }
}
