<?php

declare(strict_types=1);

namespace App\Application\EmployeeLeave;

use App\Application\EmployeeLeave\Dto\EmployeeLeaveDetailInfo;

// Domain Layer
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;
use App\Domain\EmployeeLeave\ValueObject\EmployeeSnapshot;

/**
 * Builds the EmployeeLeaveDetailInfo payload.
 *
 * Loads the Employee entity and captures an EmployeeSnapshot so the leave
 * record stays readable even if the employee master data changes afterwards.
 * Returns an empty snapshot when the employee cannot be found, so the leave
 * record can still be created (the employee_id remains on record).
 */
final readonly class EmployeeLeaveDetailInfoFactory
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
    ) {}

    public function buildFromPrimitives(?int $employeeId = null): EmployeeLeaveDetailInfo
    {
        $employee = null;

        if ($employeeId !== null) {
            $employeeEntity = $this->employeeRepository->findById($employeeId);

            $snapshot = $employeeEntity === null
                ? EmployeeSnapshot::empty()
                : EmployeeSnapshot::fromEmployee($employeeEntity);

            $employee = $snapshot->toArray();
        }

        return new EmployeeLeaveDetailInfo(
            employee: $employee,
        );
    }
}
