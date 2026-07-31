<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebtTransaction;

use App\Application\EmployeeDebtTransaction\Dto\EmployeeDebtTransactionDetailInfo;
use App\Domain\EmployeeDebt\Repository\EmployeeDebtRepositoryInterface;
use App\Domain\EmployeeDebtInstallment\Repository\EmployeeDebtInstallmentRepositoryInterface;
use App\Domain\PayrollDeduction\Repository\PayrollDeductionRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * Membangun EmployeeDebtTransactionDetailInfo dari ID relasi
 * (employee_debt_id, employee_debt_installment_id, payroll_deduction_id).
 *
 * Mengikuti pola EmployeeDebtDetailInfoFactory: lookup relasi via repository,
 * throw NotFoundException bila relasi tidak ditemukan, kembalikan DTO ringkas
 * untuk disimpan di kolom detail_info JSON.
 */
final readonly class EmployeeDebtTransactionDetailInfoFactory
{
    public function __construct(
        private EmployeeDebtRepositoryInterface $employeeDebtRepository,
        private EmployeeDebtInstallmentRepositoryInterface $installmentRepository,
        private PayrollDeductionRepositoryInterface $payrollDeductionRepository,
    ) {}

    public function buildFromIds(
        int $employeeDebtId,
        ?int $employeeDebtInstallmentId = null,
        ?int $payrollDeductionId = null,
    ): EmployeeDebtTransactionDetailInfo {
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

        $installment = null;
        if ($employeeDebtInstallmentId !== null) {
            $inst = $this->installmentRepository->findById(
                id: $employeeDebtInstallmentId,
                status: null
            );

            if ($inst === null) {
                throw new NotFoundException(
                    translate: Message::create(
                        key: 'resource.not_found',
                        params: [
                            'resource' => 'EmployeeDebtInstallment',
                            'field' => 'id',
                            'value' => $employeeDebtInstallmentId,
                        ]
                    )
                );
            }

            $installment = [
                'id' => $inst->getId(),
                'name' => $inst->getName(),
                'sequence' => $inst->getSequence(),
            ];
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

        return new EmployeeDebtTransactionDetailInfo(
            employee_debt: [
                'id' => $debt->getId(),
                'name' => $debt->getName(),
            ],
            employee_debt_installment: $installment,
            payroll_deduction: $payrollDeduction,
        );
    }

    public function buildFromPrimitives(
        ?array $employee_debt = null,
        ?array $employee_debt_installment = null,
        ?array $payroll_deduction = null,
    ): EmployeeDebtTransactionDetailInfo {
        return new EmployeeDebtTransactionDetailInfo(
            employee_debt: $employee_debt,
            employee_debt_installment: $employee_debt_installment,
            payroll_deduction: $payroll_deduction,
        );
    }
}
