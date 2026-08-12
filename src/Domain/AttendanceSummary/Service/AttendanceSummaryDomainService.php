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

        [$periodSummary, $timeSummary] = $this->calculateSummaries($attendanceDetail);

        $name = Name::fromEmployeeAndPeriod(
            employeeId: $employee->getId(),
            period: $period,
        )->getValue();

        $this->ensureUniqueName($name);

        [$snapshotData, $workPatternSnapshot] = $this->buildEmployeeSnapshot($employee);

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

    /**
     * Calculates period and time summaries from attendance detail entries.
     *
     * Period summary is now rule-aware: attendance records are grouped by
     * their work_pattern_rule, and obligation/deficit/excess are computed
     * per rule against its target_count.
     *
     * @param array<int, array> $attendanceDetail
     * @return array{0: array, 1: array}
     */
    private function calculateSummaries(array $attendanceDetail): array
    {
        $presentDays = 0;
        $lateCount = 0;
        $lateMinTotal = 0;
        $earlyOutCount = 0;
        $earlyOutMinTotal = 0;
        $incompleteCount = 0;

        // Group fulfilled attendance by rule_id
        $ruleStats = []; // rule_id => ['target' => int, 'fulfilled' => int, 'excess_treatment' => string, 'deficit_treatment' => string, 'rule_type' => string]
        $obligationDayCount = 0;

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

            if (($detail['missing_in'] ?? false) || ($detail['missing_out'] ?? false)) {
                $incompleteCount++;
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
                    $ruleStats[$ruleId] = [
                        'target' => (int)($rule['target_count'] ?? 0),
                        'fulfilled' => 0,
                        'rule_type' => $rule['rule_type'] ?? null,
                        'excess_treatment' => $rule['excess_treatment'] ?? null,
                        'deficit_treatment' => $rule['deficit_treatment'] ?? null,
                        'substitution_allowed' => (int)($rule['substitution_allowed'] ?? 0),
                    ];
                }

                if ($isFulfilled) {
                    $ruleStats[$ruleId]['fulfilled']++;
                }
            }
        }

        // Calculate per-rule aggregates
        $obligationTotalCount = 0;
        $obligationUnfulfilledCount = 0;
        $excessCount = 0;
        $overtimeDayCount = 0;
        $leaveQuotaUsedCount = 0;
        $deductionDayCount = 0;

        foreach ($ruleStats as $stat) {
            $target = $stat['target'];
            $fulfilled = $stat['fulfilled'];
            $deficit = max(0, $target - $fulfilled);
            $excess = max(0, $fulfilled - $target);

            $obligationTotalCount += $target;
            $obligationUnfulfilledCount += $deficit;

            // Apply excess_treatment
            if ($excess > 0) {
                $excessCount += $excess;
                if ($stat['excess_treatment'] === 'COUNT_AS_OVERTIME') {
                    $overtimeDayCount += $excess;
                }
            }

            // Apply deficit_treatment
            if ($deficit > 0) {
                $deficitTreatment = $stat['deficit_treatment'];
                if ($deficitTreatment === 'TREAT_AS_ABSENT') {
                    $deductionDayCount += $deficit;
                } elseif ($deficitTreatment === 'USE_EMPLOYEE_LEAVE') {
                    $leaveQuotaUsedCount += $deficit;
                }
                // MANUAL_REVIEW → no auto-action, just recorded
            }
        }

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
            $breakdown[] = [
                'rule_id' => $ruleId,
                'rule_type' => $stat['rule_type'],
                'target_count' => $stat['target'],
                'fulfilled_count' => $stat['fulfilled'],
                'deficit_count' => max(0, $stat['target'] - $stat['fulfilled']),
                'excess_count' => max(0, $stat['fulfilled'] - $stat['target']),
                'excess_treatment' => $stat['excess_treatment'],
                'deficit_treatment' => $stat['deficit_treatment'],
            ];
        }
        return $breakdown;
    }
}
