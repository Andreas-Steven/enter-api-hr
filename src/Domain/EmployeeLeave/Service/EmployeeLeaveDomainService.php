<?php

declare(strict_types=1);

namespace App\Domain\EmployeeLeave\Service;

// Application Layer
use App\Application\EmployeeLeave\Command\AssignEmployeeLeaveCommand;
use App\Application\EmployeeLeave\Dto\EmployeeLeaveResult;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\Employee\Entity\Employee;
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;
use App\Domain\EmployeeLeave\Entity\EmployeeLeave;
use App\Domain\EmployeeLeave\Repository\EmployeeLeaveRepositoryInterface;
use App\Domain\EmployeeLeave\ValueObject\EmployeeSnapshot;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\ValueObject\Message;

/**
 * EmployeeLeave Domain Service
 *
 * Pure business logic and domain rules. Owns the grand-assign flow that
 * resolves active employees by scope (branch, division, employee type, or
 * explicit IDs) and creates one EmployeeLeave per employee.
 */
final class EmployeeLeaveDomainService
{
    use DomainValidator;

    public function __construct(
        private EmployeeLeaveRepositoryInterface $repository,
        private EmployeeRepositoryInterface $employeeRepository,
        private DetailInfoFactory $detailInfoFactory,
    ) {}

    /**
     * Grand-assign: creates one EmployeeLeave per active employee matching the
     * command's scope, skipping employees that already have a leave quota for
     * the same leave_type and period range.
     *
     * For each employee:
     *  1. If the employee already has a leave quota with the same leave_type
     *     and period range, the employee is skipped (no duplicate, no
     *     adjustment - adjustment_amount is only managed via PUT /:id).
     *  2. Otherwise a new EmployeeLeave is created with an auto-generated name
     *     in the format "{baseName} - {employeeName}".
     *
     * @return EmployeeLeaveResult
     */
    public function assignToEmployees(AssignEmployeeLeaveCommand $command): EmployeeLeaveResult
    {
        $employees = $this->resolveEmployeesByScope($command->scope);

        $status = ResourceStatus::from($command->status);

        $created = 0;
        $skipped = 0;
        $errors = [];
        $items = [];

        foreach ($employees as $employee) {
            $employeeId = $employee->getId();

            if ($employeeId === null) {
                continue;
            }

            try {
                // Skip if the employee already has a leave quota for the same
                // leave type and period range. adjustment_amount is only
                // managed via PUT /v1/employee-leave/{id}.
                $existing = $this->repository->findByEmployeeTypeAndPeriod(
                    employeeId: $employeeId,
                    leaveType: $command->leaveType,
                    periodStartDate: $command->periodStartDate,
                    periodEndDate: $command->periodEndDate,
                );

                if ($existing !== null) {
                    $skipped++;
                    continue;
                }

                $name = $this->buildNameForEmployee(
                    baseName: $command->name,
                    employeeName: $employee->getName(),
                );

                // Build a per-employee detail_info with the employee snapshot
                // stored inside detail_info.employee.
                $employeeDetailInfo = $this->detailInfoFactory
                    ->create(
                        detailInfo: [
                            'employee' => EmployeeSnapshot::fromEmployee($employee)->toArray(),
                        ],
                    )
                    ->build();

                $entity = EmployeeLeave::create(
                    name: $name,
                    leaveType: $command->leaveType,
                    employeeId: $employeeId,
                    periodStartDate: $command->periodStartDate,
                    periodEndDate: $command->periodEndDate,
                    initialAmount: $command->initialAmount,
                    usedAmount: $command->usedAmount,
                    leaveRecords: $command->leaveRecords,
                    adjustmentRecords: [],
                    status: $status,
                    detailInfo: $employeeDetailInfo,
                );

                $inserted = $this->repository->insert($entity);

                $created++;
                $items[] = $inserted->toArray();
            } catch (\Throwable $e) {
                $errors[$employeeId] = $e->getMessage();
            }
        }

        return EmployeeLeaveResult::create(
            created: $created,
            adjusted: 0,
            skipped: $skipped,
            errors: $errors,
            items: $items,
        );
    }

    /**
     * Resolves active employees by the scope criteria.
     *
     * Supported scope keys (first match wins):
     *  - employee_ids: explicit list of employee IDs
     *  - branch_id:    all active employees in a branch
     *  - division_id:  all active employees in a division
     *  - employee_type_id: all active employees of a type
     *
     * @param array<string, mixed> $scope
     * @return array<int, Employee>
     */
    private function resolveEmployeesByScope(array $scope): array
    {
        if (!empty($scope['employee_ids']) && is_array($scope['employee_ids'])) {
            $ids = array_filter(
                array_map(fn($v) => (int) $v, $scope['employee_ids']),
                fn($v) => $v > 0,
            );

            return $this->employeeRepository->findByIds($ids);
        }

        if (!empty($scope['branch_id'])) {
            return $this->employeeRepository->findByBranchId((int) $scope['branch_id']);
        }

        if (!empty($scope['division_id'])) {
            return $this->employeeRepository->findByDivisionId((int) $scope['division_id']);
        }

        if (!empty($scope['employee_type_id'])) {
            return $this->employeeRepository->findByEmployeeTypeId((int) $scope['employee_type_id']);
        }

        return [];
    }

    /**
     * Builds a per-employee leave name from the base name and employee name.
     *
     * Format: "{baseName} - {employeeName}"
     */
    private function buildNameForEmployee(string $baseName, string $employeeName): string
    {
        $name = sprintf('%s - %s', $baseName, $employeeName);

        if (strlen($name) > 255) {
            $name = substr($name, 0, 255);
        }

        return $name;
    }
}
