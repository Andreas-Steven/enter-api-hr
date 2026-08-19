<?php

declare(strict_types=1);

namespace App\Application\AttendanceSummary;

// Application Layer
use App\Application\AttendanceSummary\Command\GenerateAttendanceSummaryCommand;
use App\Application\AttendanceSummary\Command\UpdateAttendanceSummaryCommand;
use App\Application\AttendanceSummary\Dto\AttendanceSummaryResponse;
use App\Application\AttendanceSummary\Dto\AttendanceSummaryResult;
use App\Application\Shared\Factory\DetailInfoFactory;
// Domain Layer
use App\Domain\AttendanceSummary\Entity\AttendanceSummary;
use App\Domain\AttendanceSummary\Repository\AttendanceSummaryRepositoryInterface;
use App\Domain\AttendanceSummary\Service\AttendanceSummaryDomainService;
use App\Domain\AttendanceSummary\ValueObject\Notes;
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
 * Orchestrates use cases and coordinates domain & infrastructure. The create
 * use case is auto-generation: given a period, the domain service builds one
 * summary per active employee from their attendance records and employee rules.
 */
final class AttendanceSummaryApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private AttendanceSummaryRepositoryInterface $repository,
        private AttendanceSummaryDomainService $domainService,
    ) {}

    public function getResource(): string
    {
        return AttendanceSummary::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): AttendanceSummary
    {
        $data = $this->repository->findById(
            id: $id,
            status: $status,
        );

        if ($data === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => $this->getResource(),
                        'field' => 'id',
                        'value' => $id,
                    ],
                ),
            );
        }

        return $data;
    }

    public function list(SearchCriteria $criteria): PaginatedResult
    {
        return $this->repository->list(
            criteria: $criteria,
        );
    }

    public function view(int $id): AttendanceSummaryResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null,
        );

        return AttendanceSummaryResponse::fromEntity(
            entity: $data,
        );
    }

    /**
     * Auto-generates AttendanceSummary records for a period.
     *
     * Delegates to the domain service which folds each employee's attendance
     * together with their work-schedule rules into one summary per employee,
     * keeping existing summaries for the period untouched.
     */
    public function generateAllFromAttendance(GenerateAttendanceSummaryCommand $command): AttendanceSummaryResult
    {
        return $this->domainService->generateAllFromAttendance(command: $command);
    }

    public function update(int $id, UpdateAttendanceSummaryCommand $command): AttendanceSummaryResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null,
        );

        $this->repository->verifyLockVersion(
            entity: $data,
            version: $command->lockVersion ?? null,
        );

        $newStatus = ResourceStatus::tryFrom($command->status);

        $hasFieldChanges = $data->hasFieldChanges(
            data: (array) $command,
            removeNulls: true,
        );

        $data->guardAgainstInvalidTransition(
            hasFieldChanges: $hasFieldChanges,
            newStatus: $newStatus,
        );

        $data->updateName(
            newName: $command->name,
        );

        $data->updateNotes(
            notes: $command->notes !== null
                ? Notes::fromNullable($command->notes)
                : null,
        );

        $data->applyStatus(
            newStatus: $newStatus,
        );

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: $command->detailInfo ?? [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo,
        );

        return AttendanceSummaryResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data,
            ),
        );
    }

    public function delete(int $id, ?int $lockVersion = null): AttendanceSummaryResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null,
        );

        $this->repository->verifyLockVersion(
            entity: $data,
            version: $lockVersion,
        );

        $this->domainService->guardPermission(
            authorizer: $this->auth,
            permission: 'attendancesummary.delete',
            resource: $this->getResource(),
            id: $id,
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
            detailInfo: $detailInfo,
        );

        return AttendanceSummaryResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data,
            ),
        );
    }

    public function restore(int $id): AttendanceSummaryResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: ResourceStatus::deleted()->value(),
        );

        $data->guardAgainstInvalidTransition(
            hasFieldChanges: false,
            newStatus: ResourceStatus::restored(),
        );

        $data->markAsRestored();

        $detailInfo = $this->detailInfoFactory
            ->restore(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo,
        );

        $restoredData = $this->repository->restore(
            id: $data->getId(),
        );

        return AttendanceSummaryResponse::fromEntity(
            entity: $restoredData,
        );
    }
}
