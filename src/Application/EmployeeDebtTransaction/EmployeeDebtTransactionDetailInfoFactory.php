<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebtTransaction;

use App\Application\EmployeeDebtTransaction\Dto\EmployeeDebtTransactionDetailInfo;
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;
use App\Domain\EmployeeDebt\Repository\EmployeeDebtRepositoryInterface;
use App\Domain\PayrollDeduction\Repository\PayrollDeductionRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * Membangun EmployeeDebtTransactionDetailInfo dari ID relasi
 * (employee_id, employee_debt_id, payroll_deduction_id).
 */
final readonly class EmployeeDebtTransactionDetailInfoFactory
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private EmployeeDebtRepositoryInterface $employeeDebtRepository,
        private PayrollDeductionRepositoryInterface $payrollDeductionRepository,
    ) {}

    public function buildFromIds(
        int $employeeId,
        int $employeeDebtId,
        ?int $payrollDeductionId = null,
    ): EmployeeDebtTransactionDetailInfo {
        $employee = $this->employeeRepository->findById(
            id: $employeeId,
            status: null
        );

        if ($employee === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => 'Employee',
                        'field' => 'id',
                        'value' => $employeeId
                    ]
                )
            );
        }

        $employeeDebt = $this->employeeDebtRepository->findById(
            id: $employeeDebtId,
            status: null
        );

        if ($employeeDebt === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => 'EmployeeDebt',
                        'field' => 'id',
                        'value' => $employeeDebtId
                    ]
                )
            );
        }

        $deduction = null;
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
                            'value' => $payrollDeductionId
                        ]
                    )
                );
            }
        }

        return new EmployeeDebtTransactionDetailInfo(
            employee: $employee->toPersistence(),
            employee_debt: $employeeDebt->toPersistence(),
            payroll_deduction: $deduction?->toPersistence(),
        );
    }

    public function buildFromPrimitives(
        ?array $employee = null,
        ?array $employee_debt = null,
        ?array $payroll_deduction = null,
    ): EmployeeDebtTransactionDetailInfo {
        return new EmployeeDebtTransactionDetailInfo(
            employee: $employee,
            employee_debt: $employee_debt,
            payroll_deduction: $payroll_deduction,
        );
    }
}
