<?php

declare(strict_types=1);

namespace App\Application\AttendanceReview;

// Application Layer
use App\Application\AttendanceReview\Command\CreateAttendanceReviewCommand;
use App\Application\AttendanceReview\Command\GenerateAttendanceReviewCommand;
use App\Application\AttendanceReview\Command\ResolveAttendanceReviewCommand;
use App\Application\AttendanceReview\Command\UpdateAttendanceReviewCommand;
use App\Application\AttendanceReview\Dto\AttendanceReviewResponse;
use App\Application\AttendanceReview\Dto\BatchGenerationResult;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\AttendanceReview\Entity\AttendanceReview;
use App\Domain\AttendanceReview\Repository\AttendanceReviewRepositoryInterface;
use App\Domain\AttendanceReview\Service\AttendanceReviewDomainService;
use App\Domain\AttendanceReview\ValueObject\SourceRef;
use App\Domain\AttendanceSummary\Repository\AttendanceSummaryRepositoryInterface;
use App\Domain\AttendanceSummary\Service\AttendanceSummaryDomainService;
use App\Domain\PayrollReport\Repository\PayrollReportRepositoryInterface;
use App\Domain\PayrollReport\Service\PayrollReportDomainService;
use App\Domain\Shared\Enums\ApprovalStatus;
use App\Domain\Shared\Enums\ReviewReason;
use App\Domain\Shared\Enums\ReviewSourceType;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * AttendanceReview Application Service
 *
 * Orchestrates use cases and coordinates domain & infrastructure.
 *
 * Key flows:
 * - generate(): Auto-creates review items from an AttendanceSummary by
 *   scanning for review-triggering treatments (MANUAL_REVIEW, REQUIRE_APPROVAL,
 *   SEND_TO_REVIEW). Uses keep-by-stable-key to avoid duplicates.
 *
 * - resolve(): Applies a manual resolution decision (approve/reject) to a
 *   review item, then cascades the decision back to the source
 *   AttendanceSummary (full recalc with overrides) and PayrollReport.
 */
final class AttendanceReviewApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private AttendanceReviewRepositoryInterface $repository,
        private AttendanceReviewDomainService $domainService,
        private AttendanceSummaryRepositoryInterface $attendanceSummaryRepository,
        private AttendanceSummaryDomainService $attendanceSummaryDomainService,
        private PayrollReportRepositoryInterface $payrollReportRepository,
        private PayrollReportDomainService $payrollReportDomainService,
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
     * Auto-generates review items for all AttendanceSummaries in a period.
     *
     * Loads every AttendanceSummary for the given period and scans each one
     * for review-triggering treatments. Creates AttendanceReview entities
     * for any that don't already exist (keep-by-stable-key). The missing-policy
     * config is resolved per-summary from the branch snapshot in detail_info,
     * unless explicitly provided in the command.
     */
    public function generate(GenerateAttendanceReviewCommand $command): BatchGenerationResult
    {
        $summaries = $this->attendanceSummaryRepository->findByPeriod(
            period: $command->period,
        );

        if (empty($summaries)) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => 'AttendanceSummary',
                        'field' => 'period',
                        'value' => $command->period,
                    ]
                )
            );
        }

        $totalCreated = 0;
        $totalSkipped = 0;
        $allErrors = [];

        foreach ($summaries as $summary) {
            // Resolve missing policy config from the summary's branch snapshot,
            // or use the explicitly provided config from the command.
            $missingPolicyConfig = $command->missingPolicyConfig;
            if ($missingPolicyConfig === null) {
                $missingPolicyConfig = $this->resolveMissingPolicyFromSummary($summary);
            }

            $generation = $this->domainService->generateFromAttendanceSummary(
                summary: $summary,
                missingPolicyConfig: $missingPolicyConfig,
            );

            $totalSkipped += $generation->skippedCount;

            foreach ($generation->entities as $entity) {
                try {
                    $this->repository->insert(entity: $entity);
                    $totalCreated++;
                } catch (\Exception $e) {
                    $allErrors[$entity->getStableKey()] = $e->getMessage();
                }
            }

            foreach ($generation->errors as $key => $error) {
                $allErrors[$key] = $error;
            }
        }

        return BatchGenerationResult::create(
            created: $totalCreated,
            skipped: $totalSkipped,
            errors: $allErrors,
        );
    }

    public function create(CreateAttendanceReviewCommand $command): AttendanceReviewResponse
    {
        $sourceRef = SourceRef::fromArray($command->sourceRef);

        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: $command->detailInfo ?? []
            )
            ->build();

        $data = AttendanceReview::create(
            name: $command->name,
            employeeId: $command->employeeId,
            period: $command->period,
            attendanceSummaryId: $command->attendanceSummaryId,
            sourceType: $command->sourceType,
            sourceRef: $sourceRef,
            reviewReason: $command->reviewReason,
            sourceTreatment: $command->sourceTreatment,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo,
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
     * Resolves a review item and cascades the decision back to the source
     * AttendanceSummary and PayrollReport.
     *
     * Flow:
     * 1. Apply the resolution decision to the review entity (domain logic)
     * 2. Persist the updated review entity
     * 3. Recalculate the source AttendanceSummary's period/time summaries
     *    using all APPROVED review overrides for that employee+period
     * 4. Persist the updated AttendanceSummary
     * 5. Recalculate the linked PayrollReport's payroll_summary
     * 6. Persist the updated PayrollReport
     */
    public function resolve(int $id, ResolveAttendanceReviewCommand $command): AttendanceReviewResponse
    {
        $review = $this->getEntityById(
            id: $id,
            status: null
        );

        $this->repository->verifyLockVersion(
            entity: $review,
            version: $command->lockVersion ?? null
        );

        $this->domainService->guardResolvable($review);

        // 1. Apply resolution decision
        $this->domainService->resolve(
            review: $review,
            decision: $command->decision,
            resolvedTreatment: $command->resolvedTreatment,
            approvedBy: null, // TODO: inject current user ID
            approvedNote: $command->approvedNote,
            approvedAt: date('Y-m-d H:i:s'),
        );

        // 2. Persist the resolved review
        $updatedReview = $this->repository->update(entity: $review);

        // 3-6. Cascade recalc (only if the review is linked to a summary)
        $summaryId = $review->getAttendanceSummaryId();
        if ($summaryId !== null) {
            $this->cascadeRecalc($summaryId, $review->getEmployeeId(), $review->getPeriod());
        }

        return AttendanceReviewResponse::fromEntity(
            entity: $updatedReview
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
        $data = $this->repository->findById(
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

        return AttendanceReviewResponse::fromEntity(
            entity: $restoredData
        );
    }

    // --- Private helpers ---

    /**
     * Extracts the missing_in_policy and missing_out_policy from the
     * AttendanceSummary's branch snapshot in detail_info.
     *
     * @return array{missing_in_policy?: ?string, missing_out_policy?: ?string}
     */
    private function resolveMissingPolicyFromSummary(
        \App\Domain\AttendanceSummary\Entity\AttendanceSummary $summary,
    ): array {
        $policyDetail = $summary->getDetailInfo()->get('branch.attendance_policies.policy_detail');

        if (!is_array($policyDetail)) {
            return [];
        }

        return [
            'missing_in_policy' => $policyDetail['missing_in_policy'] ?? null,
            'missing_out_policy' => $policyDetail['missing_out_policy'] ?? null,
        ];
    }

    /**
     * Cascades a review resolution back to the source AttendanceSummary
     * and PayrollReport by recalculating their summaries with all APPROVED
     * review overrides applied.
     */
    private function cascadeRecalc(int $summaryId, int $employeeId, int $period): void
    {
        // 3. Load all APPROVED reviews for this employee+period
        $approvedReviews = $this->repository->findByEmployeeAndPeriod(
            employeeId: $employeeId,
            period: $period,
            approvalStatus: ApprovalStatus::APPROVED,
        );

        // 4. Recalculate the AttendanceSummary with overrides
        $summary = $this->attendanceSummaryRepository->findById(id: $summaryId);
        if ($summary === null) {
            return;
        }

        $this->attendanceSummaryDomainService->recalculateForReviewOverrides(
            summary: $summary,
            reviews: $approvedReviews,
        );

        // 5. Persist the updated summary
        $this->attendanceSummaryRepository->update(entity: $summary);

        // 6. Recalculate and persist the linked PayrollReport
        $payrollReport = $this->payrollReportRepository->findByAttendanceSummaryId(
            attendanceSummaryId: $summaryId,
        );

        if ($payrollReport !== null) {
            $this->payrollReportDomainService->recalculateFromAttendanceSummary(
                payrollReport: $payrollReport,
                attendanceSummary: $summary,
            );
            $this->payrollReportRepository->update(entity: $payrollReport);
        }
    }
}
