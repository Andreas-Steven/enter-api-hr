<?php

declare(strict_types=1);

namespace App\Domain\PayrollReport\Service;

// Application Layer
use App\Application\PayrollReport\Command\GeneratePayrollReportCommand;
use App\Application\PayrollReport\Dto\BatchGenerationResult;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\AttendanceSummary\Entity\AttendanceSummary;
use App\Domain\AttendanceSummary\Repository\AttendanceSummaryRepositoryInterface;
use App\Domain\AttendanceFineRate\Entity\AttendanceFineRate;
use App\Domain\AttendanceFineRate\Repository\AttendanceFineRateRepositoryInterface;
use App\Domain\PayrollDeduction\Entity\PayrollDeduction;
use App\Domain\PayrollDeduction\Repository\PayrollDeductionRepositoryInterface;
use App\Domain\PayrollDeduction\ValueObject\Name as DeductionName;
use App\Domain\PayrollReport\Entity\PayrollReport;
use App\Domain\PayrollReport\Repository\PayrollReportRepositoryInterface;
use App\Domain\PayrollReport\Result\GenerationResult;
use App\Domain\PayrollReport\ValueObject\Name;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\Enums\IncidentType;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

/**
 * PayrollReport Domain Service
 *
 * Pure business logic and domain rules
 */
final class PayrollReportDomainService
{
    use DomainValidator;

    public function __construct(
        private DetailInfoFactory $detailInfoFactory,
        private PayrollReportRepositoryInterface $repository,
        private AttendanceSummaryRepositoryInterface $attendanceSummaryRepository,
        private AttendanceFineRateRepositoryInterface $fineRateRepository,
        private PayrollDeductionRepositoryInterface $deductionRepository,
    ) {}

    /**
     * Builds a PayrollReport entity from a single AttendanceSummary.
     *
     * Contains domain logic for:
     * - payroll_summary calculation from AttendanceSummary period/time summaries
     * - Uniqueness enforcement (domain rule)
     * - AttendanceSummary snapshot embedding
     *
     * Does NOT persist the entity — persistence is the application service's responsibility.
     */
    public function generateForAttendanceSummary(
        AttendanceSummary $attendanceSummary,
        int $period,
        ?string $notes = null,
        ?array $detailInfo = null,
    ): PayrollReport {
        $payrollSummary = $this->calculatePayrollSummary($attendanceSummary);
        $existingReport = $this->repository->findByAttendanceSummaryId(
            attendanceSummaryId: $attendanceSummary->getId(),
        );

        if ($existingReport !== null) {
            $existingReport->setPayrollSummary($payrollSummary);
            return $existingReport;
        }

        $name = Name::fromAttendanceSummaryAndPeriod(
            attendanceSummaryId: $attendanceSummary->getId(),
            period: $period,
        )->getValue();

        $this->ensureUniqueName($name);

        $snapshotData = $this->buildAttendanceSummarySnapshot($attendanceSummary);

        $builtDetailInfo = $this->detailInfoFactory
            ->create(detailInfo: array_merge($snapshotData, $detailInfo ?? []))
            ->build();

        $data = PayrollReport::create(
            name: $name,
            attendanceSummaryId: $attendanceSummary->getId(),
            status: ResourceStatus::active(),
            detailInfo: $builtDetailInfo
        );

        $data->setPayrollSummary($payrollSummary);

        if ($notes !== null) {
            $data->setNotes($notes);
        }

        return $data;
    }

    /**
     * Builds PayrollReport entities for a collection of AttendanceSummary records.
     *
     * Iterates over the summaries and delegates to generateForAttendanceSummary()
     * for each. Domain rule violations (BadRequestException, e.g. duplicate name)
     * are treated as skips; other exceptions are recorded as errors.
     *
     * Does NOT persist the entities — persistence is the application service's
     * responsibility. Returns a GenerationResult containing the built entities,
     * the skip count, and any errors encountered.
     *
     * @param AttendanceSummary[] $attendanceSummaries
     */
    public function generateBatch(
        array $attendanceSummaries,
        int $period,
        ?string $notes = null,
        ?array $detailInfo = null,
    ): GenerationResult {
        $entities = [];
        $skippedCount = 0;
        $errors = [];
        $deductions = [];

        foreach ($attendanceSummaries as $attendanceSummary) {
            try {
                $report = $this->generateForAttendanceSummary(
                    attendanceSummary: $attendanceSummary,
                    period: $period,
                    notes: $notes,
                    detailInfo: $detailInfo,
                );
                $entities[] = $report;

                // Auto-generate PayrollDeduction records from attendance incidents
                $deductions[$attendanceSummary->getId()] = $this->generateDeductionsFromAttendance(
                    attendanceSummary: $attendanceSummary,
                );
            } catch (BadRequestException $e) {
                $skippedCount++;
            } catch (\Exception $e) {
                $errors[$attendanceSummary->getId()] = $e->getMessage();
            }
        }

        return GenerationResult::create(
            entities: $entities,
            skippedCount: $skippedCount,
            errors: $errors,
            deductions: $deductions,
        );
    }

    /**
     * Orchestrates the full PayrollReport generation flow:
     * resolves source AttendanceSummary records, builds PayrollReport entities,
     * and persists them.
     */
    public function generateAllFromAttendanceSummary(GeneratePayrollReportCommand $command): BatchGenerationResult
    {
        $attendanceSummaries = $this->resolveAttendanceSummaries($command);

        $generation = $this->generateBatch(
            attendanceSummaries: $attendanceSummaries,
            period: $command->period,
            notes: $command->notes,
            detailInfo: $command->detailInfo,
        );

        return $this->persistGeneratedEntities($generation);
    }

    /**
     * Resolves the source AttendanceSummary records for generation.
     *
     * If attendanceSummaryId is provided (and > 0), only that summary is used.
     * Otherwise, all summaries for the given period are loaded.
     *
     * @return AttendanceSummary[]
     */
    private function resolveAttendanceSummaries(GeneratePayrollReportCommand $command): array
    {
        if ($command->attendanceSummaryId !== null && $command->attendanceSummaryId > 0) {
            $summary = $this->attendanceSummaryRepository->findById($command->attendanceSummaryId);
            return $summary === null ? [] : [$summary];
        }

        return $this->attendanceSummaryRepository->findByPeriod($command->period);
    }

    /**
     * Persists built PayrollReport entities and their associated PayrollDeduction
     * records, then aggregates the outcome.
     *
     * After persisting deductions, the payroll_summary is recalculated so that
     * the deduction_summary reflects the newly-persisted PayrollDeduction records.
     */
    private function persistGeneratedEntities(GenerationResult $generation): BatchGenerationResult
    {
        $created = 0;
        $errors = $generation->errors;

        foreach ($generation->entities as $entity) {
            try {
                $persistedEntity = $entity->getId() === null
                    ? $this->repository->insert(entity: $entity)
                    : $this->repository->update(entity: $entity);

                // Persist auto-generated PayrollDeduction records for this report
                $deductions = $generation->deductions[$persistedEntity->getAttendanceSummaryId()] ?? [];
                foreach ($deductions as $deduction) {
                    try {
                        $this->deductionRepository->insert(entity: $deduction);
                    } catch (\Exception $e) {
                        $errors[$deduction->getName()] = $e->getMessage();
                    }
                }

                // Recalculate payroll_summary now that deductions are persisted,
                // so deduction_summary reflects the actual PayrollDeduction records.
                $summary = $this->attendanceSummaryRepository->findById(
                    id: $persistedEntity->getAttendanceSummaryId(),
                );

                if ($summary !== null) {
                    $recalculatedSummary = $this->calculatePayrollSummary($summary);
                    $persistedEntity->setPayrollSummary($recalculatedSummary);
                    $this->repository->update(entity: $persistedEntity);
                }

                $created++;
            } catch (\Exception $e) {
                $errors[$entity->getAttendanceSummaryId()] = $e->getMessage();
            }
        }

        return BatchGenerationResult::create(
            created: $created,
            skipped: $generation->skippedCount,
            errors: $errors,
        );
    }

    /**
     * Enforces the domain rule that a PayrollReport name must be unique.
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
                        'resource' => PayrollReport::RESOURCE,
                        'field' => 'name',
                        'value' => $name
                    ]
                )
            );
        }
    }

    /**
     * Builds a snapshot of the source AttendanceSummary for embedding in
     * the PayrollReport's detail_info.
     */
    private function buildAttendanceSummarySnapshot(AttendanceSummary $attendanceSummary): array
    {
        return [
            'attendance_summary' => [
                'id' => $attendanceSummary->getId(),
                'name' => $attendanceSummary->getName(),
                'employee_id' => $attendanceSummary->getEmployeeId(),
                'period' => $attendanceSummary->getPeriod(),
            ],
            'summary_type' => 'OBLIGATION',
        ];
    }

    /**
     * Calculates the payroll_summary payload from an AttendanceSummary's
     * period_summary and time_summary, plus deduction data.
     *
     * Mapping (rule-aware):
     * - obligation_total_count       ← period_summary.obligation_total_count
     * - obligation_unfulfilled_count ← period_summary.obligation_unfulfilled_count
     * - daily_anomaly_count          ← time_summary.incomplete_count
     * - daily_late_count             ← time_summary.late_count
     * - excess_count                 ← period_summary.excess_count
     * - overtime_day_count           ← period_summary.overtime_day_count
     * - leave_quota_used_count       ← period_summary.leave_quota_used_count
     * - deduction_day_count          ← period_summary.deduction_day_count
     * - pending_review_count         ← period_summary.pending_review_count
     * - deduction_summary            ← PayrollDeduction records + period_summary.deduction_day_count
     * - rule_breakdown               ← period_summary.rule_breakdown (audit trail)
     */
    private function calculatePayrollSummary(AttendanceSummary $attendanceSummary): array
    {
        $periodSummary = $attendanceSummary->getPeriodSummary();
        $timeSummary = $attendanceSummary->getTimeSummary();

        // Build deduction_summary from persisted PayrollDeduction records
        $deductions = $this->deductionRepository->findByEmployeeAndPeriod(
            employeeId: $attendanceSummary->getEmployeeId(),
            period: $attendanceSummary->getPeriod(),
        );

        $deductionSummary = $this->buildDeductionSummary($deductions);
        $deductionSummary = $this->includeAttendanceSummaryAbsentDays(
            deductionSummary: $deductionSummary,
            expectedAbsentCount: (int) ($periodSummary['deduction_day_count'] ?? 0),
        );

        return [
            'obligation_total_count' => (int) ($periodSummary['obligation_total_count'] ?? 0),
            'obligation_unfulfilled_count' => (int) ($periodSummary['obligation_unfulfilled_count'] ?? 0),
            'daily_anomaly_count' => (int) ($timeSummary['incomplete_count'] ?? 0),
            'daily_late_count' => (int) ($timeSummary['late_count'] ?? 0),
            'excess_count' => (int) ($periodSummary['excess_count'] ?? 0),
            'overtime_day_count' => (int) ($periodSummary['overtime_day_count'] ?? 0),
            'leave_quota_used_count' => (int) ($periodSummary['leave_quota_used_count'] ?? 0),
            'deduction_day_count' => (int) ($periodSummary['deduction_day_count'] ?? 0),
            'pending_review_count' => (int) ($periodSummary['pending_review_count'] ?? 0),
            'deduction_summary' => $deductionSummary,
            'rule_breakdown' => $periodSummary['rule_breakdown'] ?? [],
        ];
    }

    /**
     * Builds a deduction_summary array from PayrollDeduction records.
     *
     * @param PayrollDeduction[] $deductions
     */
    private function buildDeductionSummary(array $deductions): array
    {
        $totalAmount = 0;
        $fineCount = 0;
        $fineAmount = 0;
        $deductionCount = 0;
        $deductionAmount = 0;

        $breakdown = [
            IncidentType::LATE->value => ['count' => 0, 'amount' => 0],
            IncidentType::EARLY_OUT->value => ['count' => 0, 'amount' => 0],
            IncidentType::MISSING_IN->value => ['count' => 0, 'amount' => 0],
            IncidentType::MISSING_OUT->value => ['count' => 0, 'amount' => 0],
            IncidentType::ABSENT->value => ['count' => 0, 'amount' => 0],
        ];

        foreach ($deductions as $deduction) {
            $amount = $deduction->getAmount();
            $totalAmount += $amount;

            $incidentTypeStr = $deduction->getDetailInfo()->get('incident_type');

            if ($incidentTypeStr !== null && isset($breakdown[$incidentTypeStr])) {
                $breakdown[$incidentTypeStr]['count']++;
                $breakdown[$incidentTypeStr]['amount'] += $amount;

                $incidentType = IncidentType::tryFrom((string) $incidentTypeStr);
                if ($incidentType !== null && $incidentType->isFine()) {
                    $fineCount++;
                    $fineAmount += $amount;
                } elseif ($incidentType !== null && $incidentType->isDeduction()) {
                    $deductionCount++;
                    $deductionAmount += $amount;
                }
            }
        }

        return [
            'total_amount' => $totalAmount,
            'fine_count' => $fineCount,
            'fine_amount' => $fineAmount,
            'deduction_count' => $deductionCount,
            'deduction_amount' => $deductionAmount,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Includes AttendanceSummary absent days that are not represented by
     * persisted PayrollDeduction records.
     *
     * @param array<string, mixed> $deductionSummary
     * @return array<string, mixed>
     */
    private function includeAttendanceSummaryAbsentDays(
        array $deductionSummary,
        int $expectedAbsentCount,
    ): array {
        $recordedAbsentCount = (int) ($deductionSummary['breakdown'][IncidentType::ABSENT->value]['count'] ?? 0);
        $missingAbsentCount = max(0, $expectedAbsentCount - $recordedAbsentCount);

        if ($missingAbsentCount === 0) {
            return $deductionSummary;
        }

        $absentRate = $this->fineRateRepository->findByIncidentType(
            incidentType: IncidentType::ABSENT,
            status: ResourceStatus::active()->value(),
        );
        $absentAmount = ($absentRate?->getAmount() ?? 0) * $missingAbsentCount;

        $deductionSummary['breakdown'][IncidentType::ABSENT->value]['count'] = $recordedAbsentCount + $missingAbsentCount;
        $deductionSummary['breakdown'][IncidentType::ABSENT->value]['amount'] += $absentAmount;
        $deductionSummary['total_amount'] += $absentAmount;
        $deductionSummary['deduction_count'] += $missingAbsentCount;
        $deductionSummary['deduction_amount'] += $absentAmount;

        return $deductionSummary;
    }

    /**
     * Recalculates an existing PayrollReport's payroll_summary from an
     * updated AttendanceSummary.
     *
     * This is the sync-back mechanism for the review cascade: after a
     * review item is resolved and the source AttendanceSummary is
     * recalculated, the linked PayrollReport's payroll_summary is
     * recomputed to reflect the updated summaries.
     *
     * The method mutates the passed PayrollReport entity in-place
     * (updates payrollSummary). Persistence is the caller's responsibility.
     */
    public function recalculateFromAttendanceSummary(
        PayrollReport $payrollReport,
        AttendanceSummary $attendanceSummary,
    ): void {
        $payrollSummary = $this->calculatePayrollSummary($attendanceSummary);
        $payrollReport->setPayrollSummary($payrollSummary);
    }

    /**
     * Scans an AttendanceSummary's attendance_detail and rule breakdown for
     * attendance incidents (late, early_out, missing_in, missing_out, absent)
     * and builds PayrollDeduction entities using rates from AttendanceFineRate.
     *
     * One PayrollDeduction per incident (per day or rule deficit). Skips
     * incidents that already have a deduction record (dedup by name).
     *
     * Does NOT persist the entities — persistence is the caller's responsibility.
     *
     * @return PayrollDeduction[]
     */
    public function generateDeductionsFromAttendance(AttendanceSummary $attendanceSummary): array
    {
        $employeeId = $attendanceSummary->getEmployeeId();
        $period = $attendanceSummary->getPeriod();
        $attendanceDetail = $attendanceSummary->getAttendanceDetail();

        // Pre-load all fine rates indexed by incident type for efficient lookup.
        $rates = $this->loadFineRates();

        $deductions = [];
        $sequence = 0;
        $representedAbsentByRule = [];

        foreach ($attendanceDetail as $entry) {
            $date = $entry['date'] ?? null;
            if ($date === null) {
                continue;
            }

            $info = $entry['info'] ?? [];
            $detail = $info['detail'] ?? [];
            $isObligationDay = $entry['is_obligation_day'] ?? false;
            $rule = $entry['work_pattern_rule'] ?? null;

            // Detect incidents
            $incidents = [];

            if ($detail['late'] ?? false) {
                $incidents[] = IncidentType::LATE;
            }

            if (($detail['early_out'] ?? 0) > 0) {
                $incidents[] = IncidentType::EARLY_OUT;
            }

            if ($detail['missing_in'] ?? false) {
                $incidents[] = IncidentType::MISSING_IN;
            }

            if ($detail['missing_out'] ?? false) {
                $incidents[] = IncidentType::MISSING_OUT;
            }

            // Absent: obligation day with no full_day and no half_day
            if ($isObligationDay && !($detail['full_day'] ?? false) && !($detail['half_day'] ?? false)) {
                $incidents[] = IncidentType::ABSENT;

                if (is_array($rule) && isset($rule['id'])) {
                    $ruleId = (int) $rule['id'];
                    $representedAbsentByRule[$ruleId] = ($representedAbsentByRule[$ruleId] ?? 0) + 1;
                }
            }

            foreach ($incidents as $incidentType) {
                $deduction = $this->buildDeductionForIncident(
                    employeeId: $employeeId,
                    period: $period,
                    date: (string) $date,
                    incidentType: $incidentType,
                    rates: $rates,
                    sequence: $sequence,
                );

                if ($deduction !== null) {
                    $deductions[] = $deduction;
                    $sequence++;
                }
            }
        }

        return [
            ...$deductions,
            ...$this->generateDeductionsFromRuleBreakdown(
                attendanceSummary: $attendanceSummary,
                rates: $rates,
                representedAbsentByRule: $representedAbsentByRule,
            ),
        ];
    }

    /**
     * Creates absent deductions for rule deficits that have no attendance entry.
     *
     * @param array<string, AttendanceFineRate> $rates
     * @param array<int, int> $representedAbsentByRule
     * @return PayrollDeduction[]
     */
    private function generateDeductionsFromRuleBreakdown(
        AttendanceSummary $attendanceSummary,
        array $rates,
        array $representedAbsentByRule,
    ): array {
        $periodSummary = $attendanceSummary->getPeriodSummary();
        $ruleBreakdown = $periodSummary['rule_breakdown'] ?? [];

        if (!is_array($ruleBreakdown)) {
            return [];
        }

        $deductions = [];
        foreach ($ruleBreakdown as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $ruleId = (int) ($rule['rule_id'] ?? 0);
            $deficit = (int) ($rule['deficit_count'] ?? 0);
            $deficitTreatment = $rule['deficit_treatment'] ?? null;

            if ($ruleId <= 0 || $deficit <= 0 || $deficitTreatment !== 'TREAT_AS_ABSENT') {
                continue;
            }

            $representedAbsent = (int) ($representedAbsentByRule[$ruleId] ?? 0);
            $missingAbsentCount = max(0, $deficit - $representedAbsent);

            for ($index = 1; $index <= $missingAbsentCount; $index++) {
                $deduction = $this->buildDeductionForRuleDeficit(
                    employeeId: $attendanceSummary->getEmployeeId(),
                    period: (int) ($attendanceSummary->getPeriod() ?? 0),
                    rule: $rule,
                    rates: $rates,
                    index: $index,
                );

                if ($deduction !== null) {
                    $deductions[] = $deduction;
                }
            }
        }

        return $deductions;
    }

    /**
     * Builds a PayrollDeduction for one absent day implied by a rule deficit.
     *
     * @param array<string, mixed> $rule
     * @param array<string, AttendanceFineRate> $rates
     */
    private function buildDeductionForRuleDeficit(
        int $employeeId,
        int $period,
        array $rule,
        array $rates,
        int $index,
    ): ?PayrollDeduction {
        $incidentType = IncidentType::ABSENT;
        $rate = $rates[$incidentType->value] ?? null;

        if ($rate === null) {
            return null;
        }

        $ruleId = (int) ($rule['rule_id'] ?? 0);
        $ruleType = (string) ($rule['rule_type'] ?? '');
        $name = sprintf(
            'DEDUCTION-E%d-P%d-ABSENT-RULE%d-%d',
            $employeeId,
            $period,
            $ruleId,
            $index,
        );

        if ($this->deductionRepository->existsByName($name)) {
            return null;
        }

        $detailInfo = \App\Domain\Shared\ValueObject\DetailInfo::fromArray([
            'incident_type' => $incidentType->value,
            'rule_id' => $ruleId,
            'rule_type' => $ruleType,
            'deficit_index' => $index,
            'auto_generated' => true,
            'source' => 'RULE_DEFICIT',
            'rate_id' => $rate->getId(),
            'change_log' => [
                'created_at' => null,
                'created_by' => null,
                'deleted_at' => null,
                'deleted_by' => null,
                'updated_at' => null,
                'updated_by' => null,
            ],
        ]);

        return PayrollDeduction::create(
            name: $name,
            employeeId: $employeeId,
            deductionTypeId: $rate->getDeductionTypeId(),
            employeeDebtId: null,
            period: $period,
            amount: $rate->getAmount(),
            note: sprintf('Tidak masuk berdasarkan rule %d', $ruleId),
            status: ResourceStatus::active(),
            detailInfo: $detailInfo,
        );
    }

    /**
     * Loads all active AttendanceFineRate records indexed by incident type.
     *
     * @return array<string, AttendanceFineRate>
     */
    private function loadFineRates(): array
    {
        $rates = [];
        foreach (IncidentType::cases() as $incidentType) {
            $rate = $this->fineRateRepository->findByIncidentType(
                incidentType: $incidentType,
                status: ResourceStatus::active()->value(),
            );
            if ($rate !== null) {
                $rates[$incidentType->value] = $rate;
            }
        }
        return $rates;
    }

    /**
     * Builds a PayrollDeduction entity for a single incident.
     *
     * Returns null if:
     * - No rate is configured for this incident type (skip silently)
     * - A deduction already exists for this incident (dedup by name)
     */
    private function buildDeductionForIncident(
        int $employeeId,
        int $period,
        string $date,
        IncidentType $incidentType,
        array $rates,
        int $sequence,
    ): ?PayrollDeduction {
        $rate = $rates[$incidentType->value] ?? null;
        if ($rate === null) {
            return null;
        }

        // Descriptive unique name: DEDUCTION-E{emp}-P{period}-{TYPE}-{date}
        $name = sprintf(
            'DEDUCTION-E%d-P%d-%s-%s',
            $employeeId,
            $period,
            $incidentType->value,
            $date,
        );

        // Dedup: skip if a deduction with this name already exists
        if ($this->deductionRepository->existsByName($name)) {
            return null;
        }

        $note = sprintf(
            '%s pada %s',
            $incidentType->getLabel(),
            $date,
        );

        $detailInfo = \App\Domain\Shared\ValueObject\DetailInfo::fromArray([
            'incident_type' => $incidentType->value,
            'incident_date' => $date,
            'rate_id' => $rate->getId(),
            'auto_generated' => true,
            'change_log' => [
                'created_at' => null,
                'created_by' => null,
                'deleted_at' => null,
                'deleted_by' => null,
                'updated_at' => null,
                'updated_by' => null,
            ],
        ]);

        return PayrollDeduction::create(
            name: $name,
            employeeId: $employeeId,
            deductionTypeId: $rate->getDeductionTypeId(),
            employeeDebtId: null,
            period: $period,
            amount: $rate->getAmount(),
            note: $note,
            status: ResourceStatus::active(),
            detailInfo: $detailInfo,
        );
    }
}
