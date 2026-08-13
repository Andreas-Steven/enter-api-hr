<?php

declare(strict_types=1);

namespace App\Domain\AttendanceSummary\Service;

// Application Layer
use App\Application\AttendanceSummary\AttendanceSummaryDetailInfoFactory;
use App\Application\AttendanceSummary\Command\GenerateAttendanceSummaryCommand;
use App\Application\AttendanceSummary\Dto\BatchGenerationResult;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\AttendanceSummary\Entity\AttendanceSummary;
use App\Domain\AttendanceSummary\Repository\AttendanceSummaryRepositoryInterface;
use App\Domain\AttendanceSummary\Result\GenerationResult;
use App\Domain\AttendanceSummary\ValueObject\Name;
use App\Domain\AttendanceReview\Entity\AttendanceReview;
use App\Domain\AttendanceReview\ValueObject\ReviewOverrides;
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;
use App\Domain\Employee\ValueObject\EmployeeCollection;
use App\Domain\Employee\Entity\Employee;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

/**
 * AttendanceSummary Domain Service
 *
 * Pure business logic and domain rules
 */
final class AttendanceSummaryDomainService
{
    use DomainValidator;

    public function __construct(
        private DetailInfoFactory $detailInfoFactory,
        private AttendanceSummaryDetailInfoFactory $summaryDetailInfoFactory,
        private AttendanceSummaryRepositoryInterface $repository,
        private EmployeeRepositoryInterface $employeeRepository,
    ) {}

    /**
     * Builds an AttendanceSummary entity from attendance data for a single employee.
     *
     * Contains domain logic for:
     * - Attendance detail aggregation and transformation
     * - Period/time summary calculation
     * - Uniqueness enforcement (domain rule)
     * - Employee snapshot creation
     *
     * Does NOT persist the entity — persistence is the application service's responsibility.
     */
    public function generateForEmployee(
        Employee $employee,
        int $period,
        ?string $notes = null,
        ?array $detailInfo = null,
    ): AttendanceSummary {
        $attendanceDetail = $this->summaryDetailInfoFactory->buildAttendanceDetail(
            employeeId: $employee->getId(),
            period: $period,
        );

        [$snapshotData, $workPatternSnapshot] = $this->buildEmployeeSnapshot($employee);

        [$periodSummary, $timeSummary] = $this->calculateSummaries(
            attendanceDetail: $attendanceDetail,
            workPatternSnapshot: $workPatternSnapshot,
        );

        $name = Name::fromEmployeeAndPeriod(
            employeeId: $employee->getId(),
            period: $period,
        )->getValue();

        $this->ensureUniqueName($name);

        $builtDetailInfo = $this->detailInfoFactory
            ->create(detailInfo: array_merge($snapshotData, $detailInfo ?? []))
            ->build();

        $data = AttendanceSummary::create(
            name: $name,
            employeeId: $employee->getId(),
            branchId: $employee->getBranchId(),
            divisionId: $employee->getDivisionId(),
            positionId: $employee->getPositionId(),
            employeeTypeId: $employee->getEmployeeTypeId(),
            status: ResourceStatus::active(),
            detailInfo: $builtDetailInfo
        );

        $data->setPeriod($period);
        $data->setAttendanceDetail($attendanceDetail);
        $data->setPeriodSummary($periodSummary);
        $data->setTimeSummary($timeSummary);

        if ($workPatternSnapshot !== null && isset($workPatternSnapshot['id'])) {
            $data->setWorkPatternId((int) $workPatternSnapshot['id']);
        }

        if ($notes !== null) {
            $data->setNotes($notes);
        }

        return $data;
    }

    /**
     * Builds AttendanceSummary entities for a collection of employees.
     *
     * Iterates over the employees and delegates to generateForEmployee() for
     * each. Domain rule violations (BadRequestException, e.g. duplicate name)
     * are treated as skips; other exceptions are recorded as errors.
     *
     * Does NOT persist the entities — persistence is the application service's
     * responsibility. Returns a GenerationResult containing the built entities,
     * the skip count, and any errors encountered.
     */
    public function generateBatch(
        EmployeeCollection $employees,
        int $period,
        ?string $notes = null,
        ?array $detailInfo = null,
    ): GenerationResult {
        $entities = [];
        $skippedCount = 0;
        $errors = [];

        foreach ($employees as $employee) {
            try {
                $entities[] = $this->generateForEmployee(
                    employee: $employee,
                    period: $period,
                    notes: $notes,
                    detailInfo: $detailInfo,
                );
            } catch (BadRequestException $e) {
                $skippedCount++;
            } catch (\Exception $e) {
                $errors[$employee->getId()] = $e->getMessage();
            }
        }

        return GenerationResult::create(
            entities: $entities,
            skippedCount: $skippedCount,
            errors: $errors,
        );
    }

    /**
     * Orchestrates the full AttendanceSummary generation flow:
     * resolves source employees, builds AttendanceSummary entities,
     * and persists them.
     */
    public function generateAllFromAttendance(GenerateAttendanceSummaryCommand $command): BatchGenerationResult
    {
        $employees = $this->resolveEmployees($command);

        $generation = $this->generateBatch(
            employees: $employees,
            period: $command->period,
            notes: $command->notes,
            detailInfo: $command->detailInfo,
        );

        return $this->persistGeneratedEntities($generation);
    }

    /**
     * Resolves the source employees for generation.
     *
     * If employeeId is provided (and > 0), only that employee is used.
     * Otherwise, all active employees are loaded.
     */
    private function resolveEmployees(GenerateAttendanceSummaryCommand $command): EmployeeCollection
    {
        if ($command->employeeId !== null && $command->employeeId > 0) {
            return EmployeeCollection::fromNullable([
                $this->employeeRepository->findById($command->employeeId),
            ]);
        }

        return EmployeeCollection::fromArray(
            $this->employeeRepository->findAllActive()
        );
    }

    /**
     * Persists built AttendanceSummary entities and aggregates the outcome.
     */
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

    /**
     * Enforces the domain rule that an AttendanceSummary name must be unique.
     *
     * @throws BadRequestException
     */
    private function ensureUniqueName(string $name): void
    {
        if ($this->repository->existsByName($name)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'resource.already_exists',
                    params: [
                        'resource' => AttendanceSummary::RESOURCE,
                        'field' => 'name',
                        'value' => $name
                    ]
                )
            );
        }
    }

    /**
     * Builds a snapshot of the employee's master data (branch, division, position,
     * employee type, work pattern) for embedding in the summary's detail_info.
     *
     * @return array{0: array, 1: ?array} [snapshotData, workPatternSnapshot]
     */
    private function buildEmployeeSnapshot(Employee $employee): array
    {
        $employeeDetailInfo = $employee->getDetailInfo();
        $branchSnapshot = $employeeDetailInfo->get('branch');
        $workPatternSnapshot = $employeeDetailInfo->get('branch.work_pattern');

        if ($branchSnapshot !== null) {
            unset($branchSnapshot['work_pattern']);
        }

        $snapshotData = [
            'employee' => [
                'id' => $employee->getId(),
                'name' => $employee->getName(),
            ],
            'branch' => $branchSnapshot,
            'division' => $employeeDetailInfo->get('division'),
            'position' => $employeeDetailInfo->get('position'),
            'employee_type' => $employeeDetailInfo->get('employee_type'),
            'work_pattern' => $workPatternSnapshot,
        ];

        return [$snapshotData, $workPatternSnapshot];
    }

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
            'substitution_allowed' => (int) ($rule['substitution_allowed'] ?? 0),
        ];
    }

    /**
     * Calculates period and time summaries from attendance detail entries.
     *
     * Period summary is now rule-aware: attendance records are grouped by
     * their work_pattern_rule, and obligation/deficit/excess are computed
     * per rule against its period target.
     *
     * Review-aware: when ReviewOverrides are provided, treatments that
     * trigger manual review (MANUAL_REVIEW, REQUIRE_APPROVAL) are resolved
     * using the override treatment from approved review items. Pending
     * (unresolved) review items are counted in `pending_review_count` and
     * their deficit/excess is held (not counted toward any bucket).
     *
     * @param array<int, array> $attendanceDetail
     * @param array<string, mixed>|null $workPatternSnapshot
     * @param ReviewOverrides|null $overrides Approved review overrides to apply
     * @param array{missing_in_policy?: ?string, missing_out_policy?: ?string}|null $missingPolicyConfig Missing policy treatments for day-level evaluation
     * @return array{0: array, 1: array}
     */
    private function calculateSummaries(
        array $attendanceDetail,
        ?array $workPatternSnapshot = null,
        ?ReviewOverrides $overrides = null,
        ?array $missingPolicyConfig = null,
    ): array
    {
        $presentDays = 0;
        $lateCount = 0;
        $lateMinTotal = 0;
        $earlyOutCount = 0;
        $earlyOutMinTotal = 0;
        $incompleteCount = 0;
        $pendingReviewCount = 0;

        // Group fulfilled attendance by rule_id
        $ruleStats = $this->initializeRuleStats($workPatternSnapshot); // rule_id => ['target' => ?int, 'fulfilled' => int, 'obligation_days' => int, 'excess_treatment' => string, 'deficit_treatment' => string, 'rule_type' => string]
        $obligationDayCount = 0;

        // Day-level missing events for SEND_TO_REVIEW evaluation
        $dayLevelPendingCount = 0;

        foreach ($attendanceDetail as $entry) {
            $info = $entry['info'] ?? [];
            $detail = $info['detail'] ?? [];
            $isObligationDay = $entry['is_obligation_day'] ?? false;
            $rule = $entry['work_pattern_rule'] ?? null;

            if ($info['in'] ?? false) {
                $presentDays++;
            }

            if ($detail['late'] ?? false) {
                $lateCount++;
                $lateMinTotal += (int)($detail['late_minutes'] ?? 0);
            }

            if (($detail['early_out'] ?? 0) > 0) {
                $earlyOutCount++;
                $earlyOutMinTotal += (int)$detail['early_out'];
            }

            $missingIn = (bool)($detail['missing_in'] ?? false);
            $missingOut = (bool)($detail['missing_out'] ?? false);

            if ($missingIn || $missingOut) {
                $incompleteCount++;
            }

            // Day-level missing policy evaluation (SEND_TO_REVIEW)
            if ($missingPolicyConfig !== null && ($missingIn || $missingOut)) {
                $date = $entry['date'] ?? null;
                $missingInPolicy = $missingPolicyConfig['missing_in_policy'] ?? null;
                $missingOutPolicy = $missingPolicyConfig['missing_out_policy'] ?? null;

                // Check missing_in
                if ($missingIn && $missingInPolicy === 'SEND_TO_REVIEW') {
                    $dayOverrides = $overrides?->forDays() ?? [];
                    $dateStr = (string) $date;
                    if (isset($dayOverrides[$dateStr]) && $dayOverrides[$dateStr]['missing_in']) {
                        // Override exists — apply resolved treatment
                        $resolvedTreatment = $dayOverrides[$dateStr]['resolved_treatment'];
                        if ($resolvedTreatment === 'TREAT_AS_ABSENT') {
                            // Count as absent — handled in absent_days below
                        }
                        // IGNORE_MISSING_EVENT → do nothing
                    } else {
                        // Pending review — count it
                        $dayLevelPendingCount++;
                    }
                }

                // Check missing_out
                if ($missingOut && $missingOutPolicy === 'SEND_TO_REVIEW') {
                    $dayOverrides = $overrides?->forDays() ?? [];
                    $dateStr = (string) $date;
                    if (isset($dayOverrides[$dateStr]) && $dayOverrides[$dateStr]['missing_out']) {
                        // Override exists — apply resolved treatment
                        $resolvedTreatment = $dayOverrides[$dateStr]['resolved_treatment'];
                        if ($resolvedTreatment === 'TREAT_AS_ABSENT') {
                            // Count as absent — handled in absent_days below
                        }
                        // IGNORE_MISSING_EVENT → do nothing
                    } else {
                        // Pending review — count it
                        $dayLevelPendingCount++;
                    }
                }
            }

            // Only count obligation days
            if (!$isObligationDay) {
                continue;
            }

            $obligationDayCount++;

            // Determine if this day counts as "fulfilled" for the rule
            $isFulfilled = ($detail['full_day'] ?? false) || ($detail['half_day'] ?? false);

            if ($rule !== null && isset($rule['id'])) {
                $ruleId = (int) $rule['id'];

                if (!isset($ruleStats[$ruleId])) {
                    $ruleStats[$ruleId] = $this->createRuleStat($rule);
                }

                if ($isFulfilled) {
                    $ruleStats[$ruleId]['fulfilled']++;
                }

                $ruleStats[$ruleId]['obligation_days']++;
            }
        }

        // Calculate per-rule aggregates
        $obligationTotalCount = 0;
        $obligationUnfulfilledCount = 0;
        $excessCount = 0;
        $overtimeDayCount = 0;
        $leaveQuotaUsedCount = 0;
        $deductionDayCount = 0;

        foreach ($ruleStats as $ruleId => $stat) {
            // REQUIRED_WEEKDAY derives its target from scheduled days in the period.
            // Other rule types use target_count directly as a period target.
            $target = ($stat['rule_type'] ?? null) === 'REQUIRED_WEEKDAY'
                ? $stat['obligation_days']
                : ($stat['target'] ?? 0);
            $fulfilled = $stat['fulfilled'];
            $deficit = max(0, $target - $fulfilled);
            $excess = max(0, $fulfilled - $target);

            $ruleStats[$ruleId]['period_target'] = $target;

            $obligationTotalCount += $target;
            $obligationUnfulfilledCount += $deficit;

            // Apply excess_treatment (with override support)
            if ($excess > 0) {
                $excessTreatment = $stat['excess_treatment'];

                // Check for override (REQUIRE_APPROVAL → resolved treatment)
                if ($excessTreatment === 'REQUIRE_APPROVAL' && $overrides !== null) {
                    $overrideTreatment = $overrides->forRule((int) $ruleId, 'EXCESS_REQUIRE_APPROVAL');
                    if ($overrideTreatment !== null) {
                        $excessTreatment = $overrideTreatment;
                    } else {
                        // Pending review — hold the excess, count as pending
                        $excessCount += $excess;
                        $pendingReviewCount++;
                        continue;
                    }
                }

                $excessCount += $excess;
                if ($excessTreatment === 'COUNT_AS_OVERTIME') {
                    $overtimeDayCount += $excess;
                }
                // IGNORE_EXCESS, NO_OVERTIME → excess counted but no overtime
            }

            // Apply deficit_treatment (with override support)
            if ($deficit > 0) {
                $deficitTreatment = $stat['deficit_treatment'];

                // Check for override (MANUAL_REVIEW → resolved treatment)
                if ($deficitTreatment === 'MANUAL_REVIEW' && $overrides !== null) {
                    $overrideTreatment = $overrides->forRule((int) $ruleId, 'DEFICIT_MANUAL_REVIEW');
                    if ($overrideTreatment !== null) {
                        $deficitTreatment = $overrideTreatment;
                    } else {
                        // Pending review — hold the deficit, count as pending
                        $pendingReviewCount++;
                        continue;
                    }
                }

                if ($deficitTreatment === 'TREAT_AS_ABSENT') {
                    $deductionDayCount += $deficit;
                } elseif ($deficitTreatment === 'USE_EMPLOYEE_LEAVE') {
                    $leaveQuotaUsedCount += $deficit;
                }
                // EXCUSED or other → no action (deficit held without penalty)
            }
        }

        $pendingReviewCount += $dayLevelPendingCount;

        $periodSummary = [
            'present_days' => $presentDays,
            'absent_days' => 0,
            'leave_days' => $leaveQuotaUsedCount,
            'sick_days' => 0,
            'permission_days' => 0,
            'hek_count' => $obligationDayCount,
            'valid_present_count' => array_sum(array_map(fn($s) => $s['fulfilled'], $ruleStats)),
            'obligation_total_count' => $obligationTotalCount,
            'obligation_unfulfilled_count' => $obligationUnfulfilledCount,
            'deficit_count' => $obligationUnfulfilledCount,
            'excess_count' => $excessCount,
            'overtime_day_count' => $overtimeDayCount,
            'leave_quota_used_count' => $leaveQuotaUsedCount,
            'deduction_day_count' => $deductionDayCount,
            'pending_review_count' => $pendingReviewCount,
            'rule_breakdown' => $this->buildRuleBreakdown($ruleStats),
        ];

        $timeSummary = [
            'overtime_min_total' => 0,
            'late_count' => $lateCount,
            'late_min_total' => $lateMinTotal,
            'early_out_count' => $earlyOutCount,
            'early_out_min_total' => $earlyOutMinTotal,
            'incomplete_count' => $incompleteCount,
        ];

        return [$periodSummary, $timeSummary];
    }

    /**
     * Builds a per-rule breakdown for audit/display purposes.
     */
    private function buildRuleBreakdown(array $ruleStats): array
    {
        $breakdown = [];
        foreach ($ruleStats as $ruleId => $stat) {
            $target = $stat['period_target'] ?? $stat['target'];
            $breakdown[] = [
                'rule_id' => $ruleId,
                'rule_type' => $stat['rule_type'],
                'target_count' => $target,
                'fulfilled_count' => $stat['fulfilled'],
                'deficit_count' => max(0, $target - $stat['fulfilled']),
                'excess_count' => max(0, $stat['fulfilled'] - $target),
                'excess_treatment' => $stat['excess_treatment'],
                'deficit_treatment' => $stat['deficit_treatment'],
            ];
        }
        return $breakdown;
    }

    /**
     * Recalculates an existing AttendanceSummary's period/time summaries
     * using approved review overrides.
     *
     * This is the sync-back mechanism: when a review item is resolved
     * (approved with an override treatment), the source summary's
     * summaries are recomputed to reflect the resolver's decision.
     *
     * The method mutates the passed AttendanceSummary entity in-place
     * (updates periodSummary and timeSummary). Persistence is the
     * caller's responsibility.
     *
     * @param AttendanceReview[] $reviews All review items for the employee+period
     */
    public function recalculateForReviewOverrides(
        AttendanceSummary $summary,
        array $reviews,
    ): void {
        $overrides = ReviewOverrides::fromReviews($reviews);

        $missingPolicyConfig = $this->resolveMissingPolicyFromSummary($summary);
        $workPatternSnapshot = $summary->getDetailInfo()->get('work_pattern');

        [$periodSummary, $timeSummary] = $this->calculateSummaries(
            attendanceDetail: $summary->getAttendanceDetail(),
            workPatternSnapshot: is_array($workPatternSnapshot) ? $workPatternSnapshot : null,
            overrides: $overrides,
            missingPolicyConfig: $missingPolicyConfig,
        );

        $summary->setPeriodSummary($periodSummary);
        $summary->setTimeSummary($timeSummary);
    }

    /**
     * Extracts the missing_in_policy and missing_out_policy from the
     * summary's branch snapshot in detail_info.
     *
     * @return array{missing_in_policy?: ?string, missing_out_policy?: ?string}
     */
    private function resolveMissingPolicyFromSummary(AttendanceSummary $summary): ?array
    {
        $policyDetail = $summary->getDetailInfo()->get('branch.attendance_policies.policy_detail');

        if (!is_array($policyDetail)) {
            return null;
        }

        return [
            'missing_in_policy' => $policyDetail['missing_in_policy'] ?? null,
            'missing_out_policy' => $policyDetail['missing_out_policy'] ?? null,
        ];
    }
}
