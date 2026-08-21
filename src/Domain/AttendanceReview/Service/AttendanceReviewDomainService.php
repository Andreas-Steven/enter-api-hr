<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReview\Service;

// Application Layer
use App\Application\AttendanceReview\Command\GenerateAttendanceReviewCommand;
use App\Application\AttendanceReview\Dto\AttendanceReviewResult;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\AttendanceSummary\Entity\AttendanceSummary;
use App\Domain\AttendanceSummary\Repository\AttendanceSummaryRepositoryInterface;
use App\Domain\AttendanceSummary\ValueObject\AttendanceData;
use App\Domain\AttendanceSummary\ValueObject\Policy;
use App\Domain\AttendanceSummary\ValueObject\WorkSchedule;
use App\Domain\AttendanceReview\Entity\AttendanceReview;
use App\Domain\AttendanceReview\Repository\AttendanceReviewRepositoryInterface;
use App\Domain\AttendanceReview\ValueObject\AttendanceSummaryRef;
use App\Domain\AttendanceReview\ValueObject\EmployeeSnapshot;
use App\Domain\AttendanceReview\ValueObject\Name;
use App\Domain\AttendanceReview\ValueObject\Notes;
use App\Domain\AttendanceReview\ValueObject\ReviewItemEvidence;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\Enums\DeficitTreatment;
use App\Domain\Shared\Enums\ExcessTreatment;
use App\Domain\Shared\Enums\MissingPolicy;
use App\Domain\Shared\Enums\ReviewReason;
use App\Domain\Shared\Enums\ReviewSourceType;
use App\Domain\Shared\Enums\ReviewStatus;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\WorkPatternRule\Entity\WorkPatternRule;
use App\Domain\WorkPatternRule\Repository\WorkPatternRuleRepositoryInterface;

// Shared Layer
use App\Shared\Exception\BadRequestException;

/**
 * AttendanceReview Domain Service.
 *
 * Owns the auto-generation flow that scans each AttendanceSummary for the
 * period and builds one AttendanceReview per evidence case (one row per
 * case):
 *
 *  - DAY_LEVEL:  days where missing_in or missing_out occurred and the
 *                attendance policy's missing_*_policy is SEND_TO_REVIEW.
 *  - RULE_LEVEL: work-pattern rules where deficit or excess was detected and
 *                the rule's deficit_treatment / excess_treatment requires
 *                manual review or approval.
 *
 * Re-running for the same period is idempotent: reviews that already exist
 * for an (employee, period, evidence-key) are kept untouched (counted as
 * skipped).
 */
final class AttendanceReviewDomainService
{
    use DomainValidator;

    public function __construct(
        private DetailInfoFactory $detailInfoFactory,
        private AttendanceReviewRepositoryInterface $repository,
        private AttendanceSummaryRepositoryInterface $attendanceSummaryRepository,
        private WorkPatternRuleRepositoryInterface $workPatternRuleRepository,
    ) {}

    /**
     * Orchestrates the full AttendanceReview generation flow for a period:
     * resolves the AttendanceSummary records, scans each one for
     * review-triggering conditions and persists one review per evidence case.
     */
    public function generateAllFromAttendanceSummary(
        GenerateAttendanceReviewCommand $command,
    ): AttendanceReviewResult {
        $attendanceSummaries = $this->resolveAttendanceSummaries($command);

        $status = ResourceStatus::active();
        $created = 0;
        $skipped = 0;
        $completed = 0;
        $errors = [];

        foreach ($attendanceSummaries as $attendanceSummary) {
            $summaryId = $attendanceSummary->getId();
            $employeeId = $attendanceSummary->getEmployeeId();

            if ($summaryId === null || $employeeId === null) {
                continue;
            }

            try {
                $evidenceList = $this->buildEvidenceList($attendanceSummary);

                // Skip summaries with no review-triggering conditions:
                // only create reviews when there are evidence cases.
                if ($evidenceList === []) {
                    $completed++;
                    continue;
                }

                foreach ($evidenceList as $evidence) {
                    $name = Name::fromEmployeePeriodAndKey(
                        employeeId: $employeeId,
                        period: $command->period,
                        evidenceKey: $evidence->uniqueKey(),
                    );

                    // Keep existing reviews untouched (idempotent re-generation).
                    if ($this->repository->existsByName($name->toString())) {
                        $skipped++;
                        continue;
                    }

                    // Each evidence case gets its own detail_info with empty
                    // approval fields so withApproved() can be called later.
                    $detailInfo = $this->detailInfoFactory
                        ->create(detailInfo: $command->detailInfo ?? [])
                        ->withEmptyApproval()
                        ->build();

                    $entity = AttendanceReview::create(
                        name: $name->toString(),
                        period: $command->period,
                        employee: EmployeeSnapshot::fromArray(
                            $attendanceSummary->getEmployee()->toArray(),
                        ),
                        attendanceSummary: AttendanceSummaryRef::fromValues(
                            id: $summaryId,
                            name: $attendanceSummary->getName(),
                        ),
                        evidence: $evidence,
                        reviewStatus: ReviewStatus::OPEN,
                        status: $status,
                        detailInfo: $detailInfo,
                        notes: Notes::fromNullable($command->notes),
                    );

                    $this->repository->insert($entity);
                    $created++;
                }
            } catch (BadRequestException $e) {
                $skipped++;
            } catch (\Throwable $e) {
                $errors[$employeeId] = $e->getMessage();
            }
        }

        return AttendanceReviewResult::create(
            created: $created,
            skipped: $skipped,
            completed: $completed,
            errors: $errors,
        );
    }

    /**
     * Resolves the AttendanceSummary records to generate reviews for.
     *
     * When attendanceSummaryId is supplied only that summary is used;
     * otherwise every summary for the period is loaded.
     *
     * @return list<AttendanceSummary>
     */
    private function resolveAttendanceSummaries(
        GenerateAttendanceReviewCommand $command,
    ): array {
        if ($command->attendanceSummaryId !== null && $command->attendanceSummaryId > 0) {
            $summary = $this->attendanceSummaryRepository->findById(
                $command->attendanceSummaryId,
            );

            return $summary === null ? [] : [$summary];
        }

        return $this->attendanceSummaryRepository->findByPeriod($command->period);
    }

    /**
     * Scans a single AttendanceSummary for review-triggering conditions and
     * builds the list of ReviewItemEvidence objects (one per case).
     *
     * @return list<ReviewItemEvidence>
     */
    private function buildEvidenceList(AttendanceSummary $summary): array
    {
        $evidence = [];

        // 1. DAY_LEVEL: missing in/out with SEND_TO_REVIEW policy.
        $missingEvidence = $this->buildMissingEvidence($summary);
        $evidence = array_merge($evidence, $missingEvidence);

        // 2. RULE_LEVEL: deficit / excess with manual-review / approval treatment.
        $ruleEvidence = $this->buildRuleEvidence($summary);
        $evidence = array_merge($evidence, $ruleEvidence);

        return $evidence;
    }

    /**
     * Builds DAY_LEVEL evidence for days with missing_in or missing_out
     * where the attendance policy's missing_*_policy is SEND_TO_REVIEW.
     *
     * @return list<ReviewItemEvidence>
     */
    private function buildMissingEvidence(AttendanceSummary $summary): array
    {
        $evidence = [];
        $policy = $summary->getPolicy();
        $attendanceData = $summary->getAttendance();
        $workSchedule = $summary->getWorkSchedule();

        $missingInPolicy = $policy->getMissingInPolicy();
        $missingOutPolicy = $policy->getMissingOutPolicy();

        // If neither missing policy triggers a review, skip entirely.
        $missingInTriggers = $missingInPolicy === MissingPolicy::SEND_TO_REVIEW;
        $missingOutTriggers = $missingOutPolicy === MissingPolicy::SEND_TO_REVIEW;

        if (!$missingInTriggers && !$missingOutTriggers) {
            return [];
        }

        $reason = ReviewReason::MISSING_SEND_TO_REVIEW;
        $treatmentOptions = $reason->treatmentOptions();

        foreach ($attendanceData->toArray() as $day) {
            $date = $day['date'] ?? null;
            $detail = $day['info']['detail'] ?? [];
            $isMissingIn = !empty($detail['missing_in']);
            $isMissingOut = !empty($detail['missing_out']);

            if (!$isMissingIn && !$isMissingOut) {
                continue;
            }

            // Build one evidence per missing field (a day can have both).
            if ($isMissingIn && $missingInTriggers) {
                $evidence[] = $this->makeDayEvidence(
                    date: $date,
                    sourceField: 'missing_in',
                    reason: $reason,
                    treatmentOptions: $treatmentOptions,
                    day: $day,
                    workSchedule: $workSchedule,
                );
            }

            if ($isMissingOut && $missingOutTriggers) {
                $evidence[] = $this->makeDayEvidence(
                    date: $date,
                    sourceField: 'missing_out',
                    reason: $reason,
                    treatmentOptions: $treatmentOptions,
                    day: $day,
                    workSchedule: $workSchedule,
                );
            }
        }

        return $evidence;
    }

    /**
     * Builds a single DAY_LEVEL evidence for a missing in/out day.
     *
     * @param list<string> $treatmentOptions
     * @param array<string, mixed> $day
     */
    private function makeDayEvidence(
        ?string $date,
        string $sourceField,
        ReviewReason $reason,
        array $treatmentOptions,
        array $day,
        WorkSchedule $workSchedule,
    ): ReviewItemEvidence {
        $required = 0;
        if ($date !== null) {
            $shift = $workSchedule->findShiftForDate($date);
            $required = $this->shiftMinutes($shift);
        }

        $rule = $day['work_pattern_rule'] ?? null;

        return ReviewItemEvidence::fromValues(
            sourceType: ReviewSourceType::DAY_LEVEL,
            reason: $reason,
            ruleId: isset($rule['id']) ? (int) $rule['id'] : null,
            ruleName: $rule['name'] ?? null,
            date: $date,
            sourceField: $sourceField,
            workMinutes: isset($day['work_duration'])
                ? (int) $day['work_duration']
                : null,
            requiredMinutes: $required > 0 ? $required : null,
            shift: $date !== null ? $workSchedule->findShiftForDate($date) : null,
            detail: $day['info']['detail'] ?? [],
            treatmentOptions: $treatmentOptions,
        );
    }

    /**
     * Builds RULE_LEVEL evidence for deficit and excess conditions.
     *
     * Deficit and excess days are grouped by their work-pattern rule. For each
     * rule group, the WorkPatternRule entity is loaded to check whether its
     * treatment requires manual review or approval. If so, a single RULE_LEVEL
     * evidence is created covering all the days in that group.
     *
     * @return list<ReviewItemEvidence>
     */
    private function buildRuleEvidence(AttendanceSummary $summary): array
    {
        $evidence = [];

        $policy = $summary->getPolicy();
        $attendanceData = $summary->getAttendance();
        $workSchedule = $summary->getWorkSchedule();

        $earlyGraceOut = $policy->getEarlyGraceOut();

        // Group deficit / excess days by rule_id.
        $deficitByRule = [];  // ruleId => ['days' => [...], 'rule_name' => string|null]
        $excessByRule = [];

        foreach ($attendanceData->toArray() as $day) {
            $isObligation = $day['is_obligation_day'] ?? false;
            if (!$isObligation) {
                continue;
            }

            $workDuration = isset($day['work_duration'])
                ? (int) $day['work_duration']
                : 0;

            if ($workDuration <= 0) {
                continue;
            }

            $date = $day['date'] ?? null;
            $required = 0;
            if ($date !== null) {
                $required = $this->shiftMinutes(
                    $workSchedule->findShiftForDate($date),
                );
            }

            if ($required <= 0) {
                continue;
            }

            $diff = $workDuration - $required;
            $isFullDay = !empty($day['info']['detail']['full_day']);
            $rule = $day['work_pattern_rule'] ?? null;
            $ruleId = isset($rule['id']) ? (int) $rule['id'] : 0;
            $ruleName = $rule['name'] ?? null;

            if ($diff < -$earlyGraceOut && !$isFullDay) {
                // Deficit day.
                if (!isset($deficitByRule[$ruleId])) {
                    $deficitByRule[$ruleId] = ['days' => [], 'rule_name' => $ruleName];
                }
                $deficitByRule[$ruleId]['days'][] = $day;
            } elseif ($diff > 0) {
                // Excess day.
                if (!isset($excessByRule[$ruleId])) {
                    $excessByRule[$ruleId] = ['days' => [], 'rule_name' => $ruleName];
                }
                $excessByRule[$ruleId]['days'][] = $day;
            }
        }

        // Build deficit evidence (RULE_LEVEL) for rules with MANUAL_REVIEW treatment.
        $deficitEvidence = $this->buildTreatmentEvidence(
            byRule: $deficitByRule,
            reason: ReviewReason::DEFICIT_MANUAL_REVIEW,
            expectedTreatment: DeficitTreatment::MANUAL_REVIEW,
            sourceField: 'deficit',
        );
        $evidence = array_merge($evidence, $deficitEvidence);

        // Build excess evidence (RULE_LEVEL) for rules with REQUIRE_APPROVAL treatment.
        $excessEvidence = $this->buildTreatmentEvidence(
            byRule: $excessByRule,
            reason: ReviewReason::EXCESS_REQUIRE_APPROVAL,
            expectedTreatment: ExcessTreatment::REQUIRE_APPROVAL,
            sourceField: 'excess',
        );
        $evidence = array_merge($evidence, $excessEvidence);

        return $evidence;
    }

    /**
     * Builds RULE_LEVEL evidence for a set of rule-grouped days, filtering by
     * the expected treatment on the WorkPatternRule entity.
     *
     * @param array<int, array{days: list<array<string, mixed>>, rule_name: string|null}> $byRule
     *
     * @return list<ReviewItemEvidence>
     */
    private function buildTreatmentEvidence(
        array $byRule,
        ReviewReason $reason,
        DeficitTreatment|ExcessTreatment $expectedTreatment,
        string $sourceField,
    ): array {
        $evidence = [];
        $treatmentOptions = $reason->treatmentOptions();

        foreach ($byRule as $ruleId => $data) {
            if ($ruleId <= 0) {
                continue;
            }

            $rule = $this->workPatternRuleRepository->findById($ruleId);
            if ($rule === null) {
                continue;
            }

            // Only create evidence when the rule's treatment matches the
            // review-triggering value.
            $actualTreatment = $expectedTreatment instanceof DeficitTreatment
                ? $rule->getDeficitTreatment()
                : $rule->getExcessTreatment();

            if ($actualTreatment !== $expectedTreatment) {
                continue;
            }

            // Aggregate evidence across all days in the group.
            $totalWork = 0;
            $totalRequired = 0;
            $dayDates = [];

            foreach ($data['days'] as $day) {
                $totalWork += (int) ($day['work_duration'] ?? 0);
                $date = $day['date'] ?? null;
                if ($date !== null) {
                    $dayDates[] = $date;
                    $totalRequired += $this->shiftMinutes(
                        // Re-derive required from the day's own rule shift if available.
                        null,
                    );
                }
            }

            $evidence[] = ReviewItemEvidence::fromValues(
                sourceType: ReviewSourceType::RULE_LEVEL,
                reason: $reason,
                ruleId: $ruleId,
                ruleName: $data['rule_name'],
                date: null,
                sourceField: $sourceField,
                workMinutes: $totalWork,
                requiredMinutes: $totalRequired > 0 ? $totalRequired : null,
                shift: null,
                detail: ['dates' => $dayDates, 'day_count' => count($data['days'])],
                treatmentOptions: $treatmentOptions,
            );
        }

        return $evidence;
    }

    /**
     * Returns the scheduled shift duration in minutes for a shift snapshot.
     * Handles overnight shifts (end time on the following day).
     *
     * @param array<string, mixed>|null $shift
     */
    private function shiftMinutes(?array $shift): int
    {
        if ($shift === null) {
            return 0;
        }

        $start = $shift['start_time'] ?? null;
        $end = $shift['end_time'] ?? null;

        if (!$start || !$end) {
            return 0;
        }

        try {
            $startDt = new \DateTimeImmutable($start);
            $endDt = new \DateTimeImmutable($end);
        } catch (\Throwable) {
            return 0;
        }

        if ($endDt <= $startDt) {
            $endDt = $endDt->modify('+1 day');
        }

        return (int) round(($endDt->getTimestamp() - $startDt->getTimestamp()) / 60);
    }
}
