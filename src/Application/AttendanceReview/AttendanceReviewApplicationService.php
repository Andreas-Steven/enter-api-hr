<?php

declare(strict_types=1);

namespace App\Application\AttendanceReview;

// Application Layer
use App\Application\AttendanceReview\Command\CreateAttendanceReviewCommand;
use App\Application\AttendanceReview\Command\GenerateAttendanceReviewCommand;
use App\Application\AttendanceReview\Command\ResolveAttendanceReviewItemCommand;
use App\Application\AttendanceReview\Command\UpdateAttendanceReviewCommand;
use App\Application\AttendanceReview\Dto\AttendanceReviewResponse;
use App\Application\AttendanceReview\Dto\AttendanceReviewResult;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\AttendanceReview\Entity\AttendanceReview;
use App\Domain\AttendanceReview\Repository\AttendanceReviewRepositoryInterface;
use App\Domain\AttendanceReview\Service\AttendanceReviewDomainService;
use App\Domain\AttendanceReview\ValueObject\AttendanceSummaryRef;
use App\Domain\AttendanceReview\ValueObject\EmployeeSnapshot;
use App\Domain\AttendanceReview\ValueObject\Notes;
use App\Domain\AttendanceReview\ValueObject\ReviewItemEvidence;
use App\Domain\Shared\Enums\ReviewStatus;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * AttendanceReview Application Service (Mandor/Alur Kerja)
 *
 * Orchestrates use cases and coordinates domain & infrastructure. The create
 * use case is auto-generation: given a period, the domain service scans each
 * AttendanceSummary for review-triggering conditions and builds one
 * AttendanceReview per evidence case (one row per case).
 */
final class AttendanceReviewApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private AttendanceReviewRepositoryInterface $repository,
        private AttendanceReviewDomainService $domainService
    ) {
    }

    public function getResource(): string
    {
        return AttendanceReview::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): AttendanceReview
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

    public function view(int $id): AttendanceReviewResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );

        return AttendanceReviewResponse::fromEntity(
            entity: $data
        );
    }

    /**
     * Auto-generates AttendanceReview records for a period.
     *
     * Delegates to the domain service which scans each AttendanceSummary for
     * review-triggering conditions (missing in/out with SEND_TO_REVIEW policy,
     * deficit with MANUAL_REVIEW treatment, excess with REQUIRE_APPROVAL
     * treatment) and builds one review per evidence case.
     */
    public function generateAllFromAttendanceSummary(
        GenerateAttendanceReviewCommand $command,
    ): AttendanceReviewResult {
        return $this->domainService->generateAllFromAttendanceSummary(
            command: $command,
        );
    }

    public function create(CreateAttendanceReviewCommand $command): AttendanceReviewResponse
    {
        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: $command->detailInfo ?? []
            )
            ->withEmptyApproval()
            ->build();

        $evidence = ReviewItemEvidence::fromArray($command->evidence ?? []);
        $reviewStatus = $command->reviewStatus !== null
            ? ReviewStatus::tryFrom($command->reviewStatus) ?? ReviewStatus::OPEN
            : ReviewStatus::OPEN;

        $data = AttendanceReview::create(
            name: $command->name,
            period: $command->period,
            employee: EmployeeSnapshot::fromArray($command->employee ?? []),
            attendanceSummary: AttendanceSummaryRef::fromArray($command->attendanceSummary ?? []),
            evidence: $evidence,
            reviewStatus: $reviewStatus,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo,
            notes: Notes::fromNullable($command->notes),
        );

        return AttendanceReviewResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
    }

    public function update(int $id, UpdateAttendanceReviewCommand $command): AttendanceReviewResponse
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
            $data->updateName(
                newName: $command->name
            );
        }

        if ($command->notes !== null) {
            $data->updateNotes(
                notes: Notes::fromNullable($command->notes)
            );
        }

        if ($command->reviewStatus !== null) {
            $reviewStatus = ReviewStatus::tryFrom($command->reviewStatus);
            if ($reviewStatus !== null) {
                $data->updateReviewStatus($reviewStatus);
            }
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

        return AttendanceReviewResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    /**
     * Resolves a single review case by applying the reviewer's decision.
     *
     * The reviewer chooses a treatment from the evidence's `treatment_options`.
     * The entity records the decision in dedicated database fields, sets
     * review_status to CLOSE (2) and transitions the resource status to
     * COMPLETED (3). The detail_info is updated with approved_at / approved_by
     * via DetailInfoFactory::withApproved().
     */
    public function resolveItem(
        ResolveAttendanceReviewItemCommand $command,
    ): AttendanceReviewResponse {
        $data = $this->getEntityById(
            id: $command->reviewId,
            status: null,
        );

        $this->repository->verifyLockVersion(
            entity: $data,
            version: $command->lockVersion,
        );

        $data->resolve(
            resolvedTreatment: $command->resolvedTreatment,
            note: $command->note,
        );

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->withApproved()
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo,
        );

        return AttendanceReviewResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data,
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): AttendanceReviewResponse
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
            authorizer: $this->auth,
            permission: 'attendance-review.delete',
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
            detailInfo: $detailInfo
        );

        return AttendanceReviewResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): AttendanceReviewResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: ResourceStatus::deleted()->value()
        );

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

        return AttendanceReviewResponse::fromEntity(
            entity: $restoredData
        );
    }
}
