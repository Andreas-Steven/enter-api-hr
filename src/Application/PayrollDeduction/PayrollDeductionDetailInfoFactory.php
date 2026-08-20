<?php

declare(strict_types=1);

namespace App\Application\PayrollDeduction;

use App\Application\PayrollDeduction\Dto\PayrollDeductionDetailInfo;
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;
use App\Domain\EmployeeDebt\Repository\EmployeeDebtRepositoryInterface;
use App\Domain\PayrollDeductionType\Repository\PayrollDeductionTypeRepositoryInterface;
use App\Shared\Enums\RecordStatus;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * Membangun PayrollDeductionDetailInfo dari ID relasi
 * (employee_id, deduction_type_id, employee_debt_id).
 *
 * Mengikuti pola EmployeeDebtDetailInfoFactory: lookup relasi via repository,
 * throw NotFoundException bila relasi tidak ditemukan, kembalikan DTO ringkas
 * untuk disimpan di kolom detail_info JSON.
 */
final readonly class PayrollDeductionDetailInfoFactory
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private PayrollDeductionTypeRepositoryInterface $payrollDeductionTypeRepository,
        private EmployeeDebtRepositoryInterface $employeeDebtRepository,
    ) {}

    public function buildFromIds(
        int $employeeId,
        int $deductionTypeId,
        ?int $employeeDebtId = null,
    ): PayrollDeductionDetailInfo {
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
                        'value' => $employeeId,
                    ]
                )
            );
        }

        $deductionType = $this->payrollDeductionTypeRepository->findById(
            id: $deductionTypeId,
            status: RecordStatus::ACTIVE->value
        );

        if ($deductionType === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => 'PayrollDeductionType',
                        'field' => 'id',
                        'value' => $deductionTypeId,
                    ]
                )
            );
        }

        $debt = null;
        if ($employeeDebtId !== null) {
            $debtEntity = $this->employeeDebtRepository->findById(
                id: $employeeDebtId,
                status: null
            );

            if ($debtEntity === null) {
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

            $debt = [
                'id' => $debtEntity->getId(),
                'name' => $debtEntity->getName(),
            ];
        }

        return new PayrollDeductionDetailInfo(
            employee: [
                'id' => $employee->getId(),
                'name' => $employee->getName(),
            ],
            payroll_deduction_type: [
                'id' => $deductionType->getId(),
                'name' => $deductionType->getName(),
                'category' => $deductionType->getCategory(),
                'creates_debt' => $deductionType->getCreatesDebt()->value,
            ],
            employee_debt: $debt,
        );
    }

    public function buildFromPrimitives(
        ?array $employee = null,
        ?array $payroll_deduction_type = null,
        ?array $employee_debt = null,
    ): PayrollDeductionDetailInfo {
        return new PayrollDeductionDetailInfo(
            employee: $employee,
            payroll_deduction_type: $payroll_deduction_type,
            employee_debt: $employee_debt,
        );
    }
}
