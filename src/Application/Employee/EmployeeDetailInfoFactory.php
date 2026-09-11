<?php

declare(strict_types=1);

namespace App\Application\Employee;

use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

// Application DTO
use App\Application\Employee\Dto\EmployeeDetailInfo;

// Domain Repository
use App\Domain\EmployeeType\Repository\EmployeeTypeRepositoryInterface;
use App\Domain\EmployeeDivision\Repository\EmployeeDivisionRepositoryInterface;
use App\Domain\EmployeePosition\Repository\EmployeePositionRepositoryInterface;
use App\Domain\Branch\Repository\BranchRepositoryInterface;
use App\Domain\EmployeeGrade\Repository\EmployeeGradeRepositoryInterface;

final readonly class EmployeeDetailInfoFactory
{
    public function __construct(
        private EmployeeTypeRepositoryInterface $employeeTypeRepo,
        private EmployeeDivisionRepositoryInterface $employeeDivisionRepo,
        private EmployeePositionRepositoryInterface $employeePositionRepo,
        private BranchRepositoryInterface $branchRepo,
        private EmployeeGradeRepositoryInterface $employeeGradeRepo,
    ) {}

    public function buildFromPrimitives(
        ?int $ssoId,
        ?string $username,
        ?int $divisionId,
        ?int $positionId,
        ?int $employeeTypeId,
        ?int $branchId,
        ?int $gradeId,
    ): EmployeeDetailInfo {
        $ssoData = [
            'id' => $ssoId,
            'name' => $username,
        ];

        $employeeType = null;
        if ($employeeTypeId !== null) {
            $employeeType = $this->employeeTypeRepo->findById(
                id: $employeeTypeId,
                status: ResourceStatus::active()->value()
            );

            if ($employeeType === null) {
                throw new NotFoundException(
                    translate: Message::create(
                        key: 'resource.not_found',
                        params: [
                            'resource' => 'employee_type',
                            'field' => 'id',
                            'value' => $employeeTypeId
                        ]
                    )
                );
            }
        }

        $employeePosition = null;
        if ($positionId !== null) {
            $employeePosition = $this->employeePositionRepo->findById(
                id: $positionId,
                status: ResourceStatus::active()->value()
            );

            if ($employeePosition === null) {
                throw new NotFoundException(
                    translate: Message::create(
                        key: 'resource.not_found',
                        params: [
                            'resource' => 'employee_position',
                            'field' => 'id',
                            'value' => $positionId
                        ]
                    )
                );
            }
        }

        $employeeDivision = null;
        if ($divisionId !== null) {
            $employeeDivision = $this->employeeDivisionRepo->findById(
                id: $divisionId,
                status: ResourceStatus::active()->value()
            );

            if ($employeeDivision === null) {
                throw new NotFoundException(
                    translate: Message::create(
                        key: 'resource.not_found',
                        params: [
                            'resource' => 'employee_division',
                            'field' => 'id',
                            'value' => $divisionId
                        ]
                    )
                );
            }
        }

        $branch = null;
        if ($branchId !== null) {
            $branch = $this->branchRepo->findById(
                id: $branchId,
                status: ResourceStatus::active()->value()
            );

            if ($branch === null) {
                throw new NotFoundException(
                    translate: Message::create(
                        key: 'resource.not_found',
                        params: [
                            'resource' => 'branch',
                            'field' => 'id',
                            'value' => $branchId
                        ]
                    )
                );
            }
        }

        $employeeGrade = null;
        if ($gradeId !== null) {
            $employeeGrade = $this->employeeGradeRepo->findById(
                id: $gradeId,
                status: ResourceStatus::active()->value()
            );
        }

        return new EmployeeDetailInfo(
            sso: $ssoData,
            branch: $branch?->toPersistence(),
            division: $employeeDivision?->toPersistence(),
            position: $employeePosition?->toPersistence(),
            employeeType: $employeeType?->toPersistence(),
            grade: $employeeGrade?->toPersistence(),
        );
    }
}
