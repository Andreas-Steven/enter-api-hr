<?php

declare(strict_types=1);

namespace App\Application\AttendanceSummary;

// Application Layer
use App\Application\AttendanceSummary\Command\CreateAttendanceSummaryCommand;
use App\Application\AttendanceSummary\Command\GenerateAttendanceSummaryCommand;
use App\Application\AttendanceSummary\Command\UpdateAttendanceSummaryCommand;
use App\Application\AttendanceSummary\Dto\AttendanceSummaryResponse;
use App\Application\AttendanceSummary\Dto\BatchGenerationResult;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\AttendanceSummary\Entity\AttendanceSummary;
use App\Domain\AttendanceSummary\Repository\AttendanceSummaryRepositoryInterface;
use App\Domain\AttendanceSummary\Result\GenerationResult;
use App\Domain\AttendanceSummary\Service\AttendanceSummaryDomainService;
use App\Domain\Employee\ValueObject\EmployeeCollection;
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * AttendanceSummary Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class AttendanceSummaryApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private AttendanceSummaryRepositoryInterface $repository,
        private AttendanceSummaryDomainService $domainService,
        private EmployeeRepositoryInterface $employeeRepository,
    ) {
    }

    public function getResource(): string
    {
        return AttendanceSummary::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): AttendanceSummary
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

    public function view(int $id): AttendanceSummaryResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );
        
        return AttendanceSummaryResponse::fromEntity(
            entity: $data
        );
    }

    public function generateAllFromAttendance(GenerateAttendanceSummaryCommand $command): BatchGenerationResult
    {
        $employees = $this->resolveEmployees($command);

        $generation = $this->domainService->generateBatch(
            employees: $employees,
            period: $command->period,
            notes: $command->notes,
            detailInfo: $command->detailInfo,
        );

        return $this->persistGeneratedEntities($generation);
    }

    private function resolveEmployees(GenerateAttendanceSummaryCommand $command): EmployeeCollection
    {
        if ($command->employeeId !== null) {
            return EmployeeCollection::fromNullable([
                $this->employeeRepository->findById($command->employeeId),
            ]);
        }

        return EmployeeCollection::fromArray(
            $this->employeeRepository->findAllActive()
        );
    }

    private function persistGeneratedEntities(GenerationResult $generation): BatchGenerationResult
    {
        $created = 0;
        $errors = $generation->errors;

        foreach ($generation->entities as $entity) {
            try {
                $this->repository->insert(entity: $entity);
                $created++;
            } catch (\Exception $e) {
                $errors[$entity->getEmployeeId()] = $e->getMessage();
            }
        }

        return BatchGenerationResult::create(
            created: $created,
            skipped: $generation->skippedCount,
            errors: $errors,
        );
    }

    public function create(CreateAttendanceSummaryCommand $command): AttendanceSummaryResponse
    {
        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: []
            )
            ->build();

        $data = AttendanceSummary::create(
            name: $command->name,
            employeeId: $command->employeeId,
            branchId: $command->branchId,
            divisionId: $command->divisionId,
            positionId: $command->positionId,
            employeeTypeId: $command->employeeTypeId,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        return AttendanceSummaryResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
    }

    public function update(int $id, UpdateAttendanceSummaryCommand $command): AttendanceSummaryResponse
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

        if ($command->name !== null) {
            $data->updateName(newName: $command->name);
        }

        if ($command->employeeId !== null) {
            $data->setEmployeeId($command->employeeId);
        }
        if ($command->branchId !== null) {
            $data->setBranchId($command->branchId);
        }
        if ($command->divisionId !== null) {
            $data->setDivisionId($command->divisionId);
        }
        if ($command->positionId !== null) {
            $data->setPositionId($command->positionId);
        }
        if ($command->employeeTypeId !== null) {
            $data->setEmployeeTypeId($command->employeeTypeId);
        }
        if ($command->workPatternId !== null) {
            $data->setWorkPatternId($command->workPatternId);
        }
        if ($command->period !== null) {
            $data->setPeriod($command->period);
        }
        if ($command->attendanceDetail !== null) {
            $data->setAttendanceDetail($command->attendanceDetail);
        }
        if ($command->periodSummary !== null) {
            $data->setPeriodSummary($command->periodSummary);
        }
        if ($command->timeSummary !== null) {
            $data->setTimeSummary($command->timeSummary);
        }
        if ($command->notes !== null) {
            $data->setNotes($command->notes);
        }

        $data->applyStatus(
            newStatus: $newStatus
        );

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: $command->detailInfo ?? [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return AttendanceSummaryResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): AttendanceSummaryResponse
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
            permission: 'AttendanceSummary.delete',
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

        return AttendanceSummaryResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): AttendanceSummaryResponse
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
        
        return AttendanceSummaryResponse::fromEntity(
            entity: $restoredData
        );
    }
}