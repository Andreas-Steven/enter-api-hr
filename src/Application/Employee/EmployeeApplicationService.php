<?php

declare(strict_types=1);

namespace App\Application\Employee;

// Application Layer
use App\Application\Employee\Command\CreateEmployeeCommand;
use App\Application\Employee\Command\UpdateEmployeeCommand;
use App\Application\Employee\Dto\EmployeeResponse;
use App\Application\Shared\Factory\DetailInfoFactory;
use App\Application\Employee\EmployeeDetailInfoFactory;

// Domain Layer
use App\Domain\Employee\Entity\Employee;
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;
use App\Domain\Employee\Service\EmployeeDomainService;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Employee\ValueObject\DateFormat;
use App\Domain\Employee\ValueObject\Contact;
use App\Domain\Employee\ValueObject\Image;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * TODO:
 * [ ] Make all DetailInfoBuilder to use toPresistance
 */

/**
 * Employee Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class EmployeeApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private EmployeeDetailInfoFactory $employeeInfoFactory,
        private EmployeeRepositoryInterface $repository,
        private EmployeeDomainService $domainService
    ) {
    }

    public function getResource(): string
    {
        return Employee::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): Employee
    {
        $data = $this->repository->findById(
            id: $id,
            status: $status
        );

        if ($data === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found', 
                    params: [
                        'resource' => $this->getResource(),
                        'field' => 'id',
                        'value' => $id
                    ]
                )
            );
        }
        
        return $data;
    }
    
    public function list(SearchCriteria $criteria): PaginatedResult
    {
        return $this->repository->list(
            criteria: $criteria
        );
    }

    public function view(int $id): EmployeeResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );
        
        return EmployeeResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreateEmployeeCommand $command): EmployeeResponse
    {
        $employeeInfoFactory = $this->employeeInfoFactory->buildFromPrimitives(
            ssoId: $command->ssoId,
            username: $command->username,
            divisionId: $command->divisionId,
            positionId: $command->positionId,
            employeeTypeId: $command->employeeTypeId,
            branchId: $command->branchId,
            gradeId: $command->gradeId
        );

        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: $employeeInfoFactory->toArray()
            )
            ->build();
            
        $data = Employee::create(
            name: $command->name,
            username: $command->username,
            ssoId: $command->ssoId,
            nik: null,
            email: null,
            divisionId: $command->divisionId,
            positionId: $command->positionId,
            employeeTypeId: $command->employeeTypeId,
            branchId: $command->branchId,
            gradeId: $command->gradeId,
            birthDate: null,
            dateStart: null,
            dateResign: null,
            contact: Contact::fromArray([]),
            image: Image::fromArray([]),
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );
        return EmployeeResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
    }

    public function update(int $id, UpdateEmployeeCommand $command): EmployeeResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );

        $this->repository->verifyLockVersion(
            entity: $data,
            version: $command->lockVersion ?? null
        );

        $newStatus = ResourceStatus::tryFrom($command->status);

        $hasFieldChanges = $data->hasFieldChanges(
            data: (array) $command,
            removeNulls: true
        );

        $data->guardAgainstInvalidTransition(
            hasFieldChanges: $hasFieldChanges,
            newStatus: $newStatus
        );

        $data->updateName(
            newName: $command->name
        );

        $data->applyStatus(
            newStatus: $newStatus
        );

        if ($command->divisionId !== null) {
            $data->updateDivision(
                divisionId: $command->divisionId
            );
        }

        if ($command->positionId !== null) {
            $data->updatePosition(
                positionId: $command->positionId
            );
        }

        if ($command->employeeTypeId !== null) {
            $data->updateEmployeeType(
                employeeTypeId: $command->employeeTypeId
            );
        }

        if ($command->branchId !== null) {
            $data->updateBranch(
                branchId: $command->branchId
            );
        }

        if ($command->gradeId !== null) {
            $data->updateGrade(
                gradeId: $command->gradeId
            );
        }

        if ($command->nik !== null) {
            $data->updateNik(
                nik: $command->nik
            );
        }

        if ($command->email !== null) {
            $data->updateEmail(
                email: $command->email
            );
        }

        if ($command->birthDate !== null) {
            $data->updateBirthDate(
                date: DateFormat::fromString($command->birthDate)
            );
        }

        if ($command->dateStart !== null) {
            $data->updateDateStart(
                date: DateFormat::fromString($command->dateStart)
            );
        }

        if ($command->contact !== null) {
            $data->updateContact(
                contact: Contact::fromArray($command->contact)
            );
        }

        if ($command->image !== null) {
            $data->updateImage(
                image: Image::fromArray($command->image)
            );
        }

        if (!empty($newStatus) && $newStatus->isActive()) {
            $this->domainService->ensureCanActivate($data);
        }

        $employeeInfoFactory = $this->employeeInfoFactory->buildFromPrimitives(
            ssoId: $data->getSsoId(),
            username: $data->getUsername(),
            divisionId: $command->divisionId ?? $data->getDivisionId(),
            positionId: $command->positionId ?? $data->getPositionId(),
            employeeTypeId: $command->employeeTypeId ?? $data->getEmployeeTypeId(),
            branchId: $command->branchId ?? $data->getBranchId(),
            gradeId: $command->gradeId ?? $data->getGradeId(),
        );

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: $employeeInfoFactory->toArray()
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return EmployeeResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): EmployeeResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );

        $this->repository->verifyLockVersion(
            entity: $data, 
            version: $lockVersion,
        );

        $this->domainService->guardPermission(
            id: $id,
            authorizer: $this->auth,
            permission: 'employee.delete',
            resource: $this->getResource(),
        );
        
        $this->domainService->ensureDeletable(
            entity: $data,
            resource: $this->getResource(),
        );

        $detailInfo = $this->detailInfoFactory
            ->delete(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return EmployeeResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): EmployeeResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: ResourceStatus::deleted()->value()
        );
        
        if ($data === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found', 
                    params: [
                        'resource' => $this->getResource(),
                        'field' => 'id',
                        'value' => $id
                    ]
                )
            );
        }

        $data->guardAgainstInvalidTransition(
            hasFieldChanges: false,
            newStatus: ResourceStatus::restored()
        );

        $data->markAsRestored();

        $detailInfo = $this->detailInfoFactory
            ->restore(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        $restoredData = $this->repository->restore(
            id: $data->getId()
        );
        
        return EmployeeResponse::fromEntity(
            entity: $restoredData
        );
    }
}
