<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebt;

use App\Application\EmployeeDebt\Dto\EmployeeDebtDetailInfo;
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;
use App\Domain\PayrollDeductionType\Repository\PayrollDeductionTypeRepositoryInterface;
use App\Shared\Enums\RecordStatus;
use App\Shared\ValueObject\Message;
use App\Shared\Exception\NotFoundException;

final readonly class EmployeeDebtDetailInfoFactory
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private PayrollDeductionTypeRepositoryInterface $payrollDeductionTypeRepository,
    ) {}

    public function buildFromIds(int $employeeId, int $deductionTypeId): EmployeeDebtDetailInfo
    {
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
                        'value' => $deductionTypeId
                    ]
                )
            );
        }

        return new EmployeeDebtDetailInfo(
            employee: [
                'id' => $employee->getId(),
                'name' => $employee->getName(),
            ],
            deduction_type: [
                'id' => $deductionType->getId(),
                'name' => $deductionType->getName(),
                'category' => $deductionType->getCategory(),
                'creates_debt' => $deductionType->getCreatesDebt()->value,
            ],
        );
    }

    public function buildFromPrimitives(
        ?array $employee = null,
        ?array $deduction_type = null,
    ): EmployeeDebtDetailInfo {
        return new EmployeeDebtDetailInfo(
            employee: $employee,
            deduction_type: $deduction_type,
        );
    }
}
