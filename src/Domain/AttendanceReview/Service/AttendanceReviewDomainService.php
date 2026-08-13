<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReview\Service;

// Application Layer
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\AttendanceReview\Entity\AttendanceReview;
use App\Domain\AttendanceReview\Repository\AttendanceReviewRepositoryInterface;
use App\Domain\AttendanceReview\Result\GenerationResult;
use App\Domain\AttendanceReview\ValueObject\SourceRef;
use App\Domain\AttendanceSummary\Entity\AttendanceSummary;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\Enums\ApprovalStatus;
use App\Domain\Shared\Enums\ReviewReason;
use App\Domain\Shared\Enums\ReviewSourceType;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\ValueObject\Message;

/**
 * AttendanceReview Domain Service
 *
 * Pure business logic for:
 * - Scanning attendance data to detect review-triggering treatments
 *   (MANUAL_REVIEW, REQUIRE_APPROVAL, SEND_TO_REVIEW)
 * - Generating AttendanceReview entities for treatments that don't
 *   already have a review item (keep-by-stable-key)
 * - Applying manual resolution decisions (approve/reject with override)
 */
final class AttendanceReviewDomainService
{
    use DomainValidator;

    public function __construct(
        private DetailInfoFactory $detailInfoFactory,
        private AttendanceReviewRepositoryInterface $repository,
    ) {}

    /**
     * Scans an AttendanceSummary's attendance detail for review-triggering
     * treatments and builds AttendanceReview entities for any that don't
     * already exist (keep-by-stable-key).
     *
     * Two scan passes:
     * 1. RULE_LEVEL — groups obligation-day entries by rule_id, computes
     *    deficit/excess, and emits items for MANUAL_REVIEW / REQUIRE_APPROVAL.
     * 2. DAY_LEVEL — iterates entries with missing_in/missing_out flags,
     *    and emits items for SEND_TO_REVIEW (when the corresponding missing
     *    policy is configured as such).
     *
     * @param AttendanceSummary $summary The source summary (provides employee_id, period, attendance_detail)
     * @param array{missing_in_policy?: ?string, missing_out_policy?: ?string} $missingPolicyConfig Resolved policy treatments for the employee's branch
     */
    public function generateFromAttendanceSummary(
        AttendanceSummary $summary,
        array $missingPolicyConfig = [],
    ): GenerationResult {
        $employeeId = $summary->getEmployeeId();
        $period = $summary->getPeriod() ?? 0;
        $attendanceDetail = $summary->getAttendanceDetail();
        $summaryId = $summary->getId();

        $entities = [];
        $skippedCount = 0;
        $errors = [];

        // --- Pass 1: RULE_LEVEL scan (deficit/excess) ---
        $workPatternSnapshot = $summary->getDetailInfo()->get('work_pattern');
        $ruleStats = $this->buildRuleStats(
            attendanceDetail: $attendanceDetail,
            workPatternSnapshot: is_array($workPatternSnapshot) ? $workPatternSnapshot : null,
        );

        foreach ($ruleStats as $ruleId => $stat) {
            try {
                $target = ($stat['rule_type'] ?? null) === 'REQUIRED_WEEKDAY'
                    ? $stat['obligation_days']
                    : ($stat['target'] ?? 0);
                $deficit = max(0, $target - $stat['fulfilled']);
                $excess = max(0, $stat['fulfilled'] - $target);

                // Deficit with MANUAL_REVIEW
                if ($deficit > 0 && $stat['deficit_treatment'] === 'MANUAL_REVIEW') {
                    $entity = $this->buildRuleLevelReview(
                        employeeId: $employeeId,
                        period: $period,
                        summaryId: $summaryId,
                        ruleId: $ruleId,
                        ruleType: $stat['rule_type'],
                        targetCount: $target,
                        fulfilledCount: $stat['fulfilled'],
                        deficitCount: $deficit,
                        excessCount: 0,
                        sourceTreatment: 'MANUAL_REVIEW',
                        reviewReason: ReviewReason::DEFICIT_MANUAL_REVIEW,
                    );

                    if ($entity !== null) {
                        $entities[] = $entity;
                    } else {
                        $skippedCount++;
                    }
                }

                // Excess with REQUIRE_APPROVAL
                if ($excess > 0 && $stat['excess_treatment'] === 'REQUIRE_APPROVAL') {
                    $entity = $this->buildRuleLevelReview(
                        employeeId: $employeeId,
                        period: $period,
                        summaryId: $summaryId,
                        ruleId: $ruleId,
                        ruleType: $stat['rule_type'],
                        targetCount: $target,
                        fulfilledCount: $stat['fulfilled'],
                        deficitCount: 0,
                        excessCount: $excess,
                        sourceTreatment: 'REQUIRE_APPROVAL',
                        reviewReason: ReviewReason::EXCESS_REQUIRE_APPROVAL,
                    );

                    if ($entity !== null) {
                        $entities[] = $entity;
                    } else {
                        $skippedCount++;
                    }
                }
            } catch (\Exception $e) {
                $errors[$ruleId] = $e->getMessage();
            }
        }

        // --- Pass 2: DAY_LEVEL scan (missing_in/missing_out) ---
        $missingInPolicy = $missingPolicyConfig['missing_in_policy'] ?? null;
        $missingOutPolicy = $missingPolicyConfig['missing_out_policy'] ?? null;

        foreach ($attendanceDetail as $entry) {
            try {
                $info = $entry['info'] ?? [];
                $detail = $info['detail'] ?? [];
                $date = $entry['date'] ?? null;

                if ($date === null) {
                    continue;
                }

                $missingIn = (bool) ($detail['missing_in'] ?? false);
                $missingOut = (bool) ($detail['missing_out'] ?? false);

                if (!$missingIn && !$missingOut) {
                    continue;
                }

                // Check missing_in policy
                if ($missingIn && $missingInPolicy === 'SEND_TO_REVIEW') {
                    $entity = $this->buildDayLevelReview(
                        employeeId: $employeeId,
                        period: $period,
                        summaryId: $summaryId,
                        date: (string) $date,
                        missingIn: true,
                        missingOut: false,
                        sourceTreatment: 'SEND_TO_REVIEW',
                    );

                    if ($entity !== null) {
                        $entities[] = $entity;
                    } else {
                        $skippedCount++;
                    }
                }

                // Check missing_out policy
                if ($missingOut && $missingOutPolicy === 'SEND_TO_REVIEW') {
                    $entity = $this->buildDayLevelReview(
                        employeeId: $employeeId,
                        period: $period,
                        summaryId: $summaryId,
                        date: (string) $date,
                        missingIn: false,
                        missingOut: true,
                        sourceTreatment: 'SEND_TO_REVIEW',
                    );

                    if ($entity !== null) {
                        $entities[] = $entity;
                    } else {
                        $skippedCount++;
                    }
                }
            } catch (\Exception $e) {
                $errors[$entry['date'] ?? 'unknown'] = $e->getMessage();
            }
        }

        return GenerationResult::create(
            entities: $entities,
            skippedCount: $skippedCount,
            errors: $errors,
        );
    }

    /**
     * Applies a resolution decision to a review item.
     *
     * This is pure domain logic — it only mutates the entity's state.
     * The cascade (recalc AttendanceSummary + PayrollReport) is the
     * application service's responsibility.
     */
    public function resolve(
        AttendanceReview $review,
        ApprovalStatus $decision,
        ?string $resolvedTreatment,
        ?int $approvedBy,
        ?string $approvedNote,
        ?string $approvedAt,
    ): void {
        $review->applyResolution(
            decision: $decision,
            resolvedTreatment: $resolvedTreatment,
            approvedBy: $approvedBy,
            approvedNote: $approvedNote,
            approvedAt: $approvedAt,
        );
    }

    /**
     * Guards that a review item is in a state that allows resolution.
     */
    public function guardResolvable(AttendanceReview $review): void
    {
        if ($review->getStatus()->isDeleted()) {
            throw new \App\Shared\Exception\ConflictException(
                translate: Message::create(
                    key: 'attendance_review.deleted_not_resolvable',
                    params: ['id' => $review->getId()],
                    domain: 'app',
                )
            );
        }
    }

    // --- Private helpers ---

    private function initializeRuleStats(?array $workPatternSnapshot): array
    {
        $workingDays = $workPatternSnapshot['working_days'] ?? [];

        if (!is_array($workingDays)) {
            return [];
        }

        $ruleStats = [];
        foreach ($workingDays as $day) {
            $rule = is_array($day) ? ($day['work_pattern_rule'] ?? null) : null;

            if (!is_array($rule) || !isset($rule['id'])) {
                continue;
            }

            $ruleId = (int) $rule['id'];
            if ($ruleId <= 0 || isset($ruleStats[$ruleId])) {
                continue;
            }

            $ruleStats[$ruleId] = $this->createRuleStat($rule);
        }

        return $ruleStats;
    }

    /**
     * @param array<string, mixed> $rule
     * @return array<string, mixed>
     */
    private function createRuleStat(array $rule): array
    {
        $targetCount = $rule['target_count'] ?? null;

        return [
            'target' => $targetCount === null ? null : (int) $targetCount,
            'fulfilled' => 0,
            'obligation_days' => 0,
            'rule_type' => $rule['rule_type'] ?? null,
            'excess_treatment' => $rule['excess_treatment'] ?? null,
            'deficit_treatment' => $rule['deficit_treatment'] ?? null,
        ];
    }

    /**
     * Groups obligation-day attendance entries by rule_id and computes
     * per-rule target/fulfilled counts plus the configured treatments.
     *
     * @param array<int, array> $attendanceDetail
     * @param array<string, mixed>|null $workPatternSnapshot
     * @return array<int, array{target: ?int, fulfilled: int, obligation_days: int, rule_type: ?string, excess_treatment: ?string, deficit_treatment: ?string}>
     */
    private function buildRuleStats(
        array $attendanceDetail,
        ?array $workPatternSnapshot = null,
    ): array {
        $ruleStats = $this->initializeRuleStats($workPatternSnapshot);

        foreach ($attendanceDetail as $entry) {
            $isObligationDay = $entry['is_obligation_day'] ?? false;
            $rule = $entry['work_pattern_rule'] ?? null;

            if (!$isObligationDay || $rule === null || !isset($rule['id'])) {
                continue;
            }

            $ruleId = (int) $rule['id'];
            $info = $entry['info'] ?? [];
            $detail = $info['detail'] ?? [];
            $isFulfilled = ($detail['full_day'] ?? false) || ($detail['half_day'] ?? false);

            if (!isset($ruleStats[$ruleId])) {
                $ruleStats[$ruleId] = $this->createRuleStat($rule);
            }

            $ruleStats[$ruleId]['obligation_days']++;

            if ($isFulfilled) {
                $ruleStats[$ruleId]['fulfilled']++;
            }
        }

        return $ruleStats;
    }

    /**
     * Builds a RULE_LEVEL review entity if no review item exists for the
     * same stable key. Returns null if one already exists (skip).
     */
    private function buildRuleLevelReview(
        int $employeeId,
        int $period,
        ?int $summaryId,
        int $ruleId,
        ?string $ruleType,
        int $targetCount,
        int $fulfilledCount,
        int $deficitCount,
        int $excessCount,
        string $sourceTreatment,
        ReviewReason $reviewReason,
    ): ?AttendanceReview {
        $sourceRef = SourceRef::forRule(
            ruleId: $ruleId,
            ruleType: $ruleType,
            targetCount: $targetCount,
            fulfilledCount: $fulfilledCount,
            deficitCount: $deficitCount,
            excessCount: $excessCount,
        );

        $stableKey = $sourceRef->stableHash();

        // Keep-by-stable-key: skip if a review item already exists.
        if ($this->repository->existsByStableKey($employeeId, $period, $stableKey)) {
            return null;
        }

        $name = sprintf(
            'Review-E%d-P%d-R%d-%s',
            $employeeId,
            $period,
            $ruleId,
            $reviewReason->value,
        );

        $detailInfo = $this->detailInfoFactory
            ->create(detailInfo: [])
            ->build();

        return AttendanceReview::create(
            name: $name,
            employeeId: $employeeId,
            period: $period,
            attendanceSummaryId: $summaryId,
            sourceType: ReviewSourceType::RULE_LEVEL,
            sourceRef: $sourceRef,
            reviewReason: $reviewReason,
            sourceTreatment: $sourceTreatment,
            status: ResourceStatus::active(),
            detailInfo: $detailInfo,
        );
    }

    /**
     * Builds a DAY_LEVEL review entity if no review item exists for the
     * same stable key. Returns null if one already exists (skip).
     */
    private function buildDayLevelReview(
        int $employeeId,
        int $period,
        ?int $summaryId,
        string $date,
        bool $missingIn,
        bool $missingOut,
        string $sourceTreatment,
    ): ?AttendanceReview {
        $sourceRef = SourceRef::forDay(
            date: $date,
            missingIn: $missingIn,
            missingOut: $missingOut,
        );

        $stableKey = $sourceRef->stableHash();

        if ($this->repository->existsByStableKey($employeeId, $period, $stableKey)) {
            return null;
        }

        $name = sprintf(
            'Review-E%d-P%d-D%s-MISSING',
            $employeeId,
            $period,
            $date,
        );

        $detailInfo = $this->detailInfoFactory
            ->create(detailInfo: [])
            ->build();

        return AttendanceReview::create(
            name: $name,
            employeeId: $employeeId,
            period: $period,
            attendanceSummaryId: $summaryId,
            sourceType: ReviewSourceType::DAY_LEVEL,
            sourceRef: $sourceRef,
            reviewReason: ReviewReason::MISSING_SEND_TO_REVIEW,
            sourceTreatment: $sourceTreatment,
            status: ResourceStatus::active(),
            detailInfo: $detailInfo,
        );
    }
}
