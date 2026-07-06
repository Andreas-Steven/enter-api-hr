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

// Domain Entity
use App\Domain\EmployeeType\Entity\EmployeeType;
use App\Domain\EmployeeDivision\Entity\EmployeeDivision;
use App\Domain\EmployeePosition\Entity\EmployeePosition;
use App\Domain\Branch\Entity\Branch;
use App\Domain\EmployeeGrade\Entity\EmployeeGrade;

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
            $employeeTypeData = $this->employeeTypeRepo->findById(
                id: $employeeTypeId,
                status: ResourceStatus::active()->value()
            );

            if ($employeeTypeData === null) {
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

            $employeeType = EmployeeType::reconstitute(
                id: $employeeTypeData->getId(),
                name: $employeeTypeData->getName(),
                status: $employeeTypeData->getStatus(),
                detailInfo: $employeeTypeData->getDetailInfo(),
                syncMdb: $employeeTypeData->getSyncMdb(),
                lockVersion: $employeeTypeData->getLockVersion()
            );
        }

        $employeePosition = null;
        if ($positionId !== null) {
            $employeePositionData = $this->employeePositionRepo->findById(
                id: $positionId,
                status: ResourceStatus::active()->value()
            );

            if ($employeePositionData === null) {
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

            $employeePosition = EmployeePosition::reconstitute(
                id: $employeePositionData->getId(),
                name: $employeePositionData->getName(),
                status: $employeePositionData->getStatus(),
                detailInfo: $employeePositionData->getDetailInfo(),
                syncMdb: $employeePositionData->getSyncMdb(),
                lockVersion: $employeePositionData->getLockVersion()
            );
        }

        $employeeDivision = null;
        if ($divisionId !== null) {
            $employeeDivisionData = $this->employeeDivisionRepo->findById(
                id: $divisionId,
                status: ResourceStatus::active()->value()
            );

            if ($employeeDivisionData === null) {
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

            $employeeDivision = EmployeeDivision::reconstitute(
                id: $employeeDivisionData->getId(),
                name: $employeeDivisionData->getName(),
                code: $employeeDivisionData->getCode(),
                status: $employeeDivisionData->getStatus(),
                detailInfo: $employeeDivisionData->getDetailInfo(),
                syncMdb: $employeeDivisionData->getSyncMdb(),
                lockVersion: $employeeDivisionData->getLockVersion()
            );
        }

        $branch = null;
        if ($branchId !== null) {
            $branchData = $this->branchRepo->findById(
                id: $branchId,
                status: ResourceStatus::active()->value()
            );

            if ($branchData === null) {
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

            $branch = Branch::reconstitute(
                id: $branchData->getId(),
                name: $branchData->getName(),
                code: $branchData->getCode(),
                address: $branchData->getAddress(),
                attendancePoliciesId: $branchData->getAttendancePoliciesId(),
                workPatternId: $branchData->getWorkPatternId(),
                status: $branchData->getStatus(),
                branchType: $branchData->getType(),
                detailInfo: $branchData->getDetailInfo(),
                syncMdb: $branchData->getSyncMdb(),
                lockVersion: $branchData->getLockVersion()
            );
        }

        $employeeGrade = null;
        if ($gradeId !== null) {
            $employeeGradeData = $this->employeeGradeRepo->findById(
                id: $gradeId,
                status: ResourceStatus::active()->value()
            );

            if ($employeeGradeData !== null) {
                $employeeGrade = EmployeeGrade::reconstitute(
                    id: $employeeGradeData->getId(),
                    name: $employeeGradeData->getName(),
                    level: $employeeGradeData->getLevel(),
                    status: $employeeGradeData->getStatus(),
                    detailInfo: $employeeGradeData->getDetailInfo(),
                    syncMdb: $employeeGradeData->getSyncMdb(),
                    lockVersion: $employeeGradeData->getLockVersion()
                );
            }
        }

        $divisionSnapshot = $employeeDivision?->toPersistence();

        $branchSnapshot = $branch?->toPersistence();
        if ($branch !== null) {
            $branchSnapshot['attendance_policies'] = $branch->getDetailInfo()->get('attendance_policies');
            $branchSnapshot['work_pattern'] = $branch->getDetailInfo()->get('work_pattern');
        }

        return new EmployeeDetailInfo(
            sso: $ssoData,
            division: $divisionSnapshot,
            position: $employeePosition?->toPersistence(),
            employeeType: $employeeType?->toPersistence(),
            branch: $branchSnapshot,
            grade: $employeeGrade?->toPersistence(),
        );
    }
}