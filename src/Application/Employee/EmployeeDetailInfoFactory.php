<?php

declare(strict_types=1);

namespace App\Application\Employee;

use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

// Application DTO
use App\Application\Employee\Dto\EmployeeDetailInfo;

// Domain Repository
use App\Domain\WorkTime\Repository\WorkTimeRepositoryInterface;
use App\Domain\EmployeeType\Repository\EmployeeTypeRepositoryInterface;
use App\Domain\EmployeeDivision\Repository\EmployeeDivisionRepositoryInterface;
use App\Domain\EmployeePosition\Repository\EmployeePositionRepositoryInterface;

// Domain Entity
use App\Domain\WorkTime\Entity\WorkTime;
use App\Domain\EmployeeType\Entity\EmployeeType;
use App\Domain\EmployeeDivision\Entity\EmployeeDivision;
use App\Domain\EmployeePosition\Entity\EmployeePosition;

final readonly class EmployeeDetailInfoFactory
{
    public function __construct(
        private WorkTimeRepositoryInterface $workTimeRepo,
        private EmployeeTypeRepositoryInterface $employeeTypeRepo,
        private EmployeeDivisionRepositoryInterface $employeeDivisionRepo,
        private EmployeePositionRepositoryInterface $employeePositionRepo,
    ) {}

    public function buildFromPrimitives(
        ?int $ssoId,
        ?string $username,
        ?int $workTimeId,
        ?int $divisionId,
        ?int $positionId,
        ?int $employeeTypeId,
    ): EmployeeDetailInfo {
        $ssoData = [
            'id' => $ssoId,
            'name' => $username,
        ];

        $workTimeData = $this->workTimeRepo->findById(
            id: $workTimeId,
            status: ResourceStatus::active()->value()
        );

        if ($workTimeData === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found', 
                    params: [
                        'resource' => 'work_time',
                        'field' => 'id',
                        'value' => $workTimeId
                    ]
                )
            );
        }

        $workTime = WorkTime::reconstitute(
            id: $workTimeData->getId(),
            name: $workTimeData->getName(),
            workingDays: $workTimeData->getWorkingDays(),
            status: $workTimeData->getStatus(),
            detailInfo: $workTimeData->getDetailInfo(),
            syncMdb: $workTimeData->getSyncMdb(),
            lockVersion: $workTimeData->getLockVersion()
        );

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

        return new EmployeeDetailInfo(
            sso: $ssoData,
            division: $employeeDivision->toPersistence(),
            position: $employeePosition->toPersistence(),
            employeeType: $employeeType->toPersistence(),
            workTime: $workTime->toPersistence(),
        );
    }

    public function toArray(): array
    {
        return [
            'sso' => $this->sso,
            'division' => $this->division,
            'position' => $this->position,
            'employeeType' => $this->employeeType,
            'workTime' => $this->workTime,
            'username' => $this->username,
        ];
    }
}