<?php

declare(strict_types=1);

namespace App\Domain\PayrollReport\Service;

// Application Layer
use App\Application\PayrollReport\Command\GeneratePayrollReportCommand;
use App\Application\PayrollReport\Dto\PayrollReportResult;
use App\Application\PayrollDeduction\PayrollDeductionDetailInfoFactory;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\AttendanceSummary\Entity\AttendanceSummary;
use App\Domain\AttendanceSummary\Repository\AttendanceSummaryRepositoryInterface;
use App\Domain\PayrollRatePolicies\Entity\PayrollRatePolicies;
use App\Domain\PayrollRatePolicies\Repository\PayrollRatePoliciesRepositoryInterface;
use App\Domain\PayrollDeduction\Entity\PayrollDeduction;
use App\Domain\PayrollDeduction\Repository\PayrollDeductionRepositoryInterface;
use App\Domain\PayrollDeduction\ValueObject\Name as DeductionName;
use App\Domain\PayrollReport\Entity\PayrollReport;
use App\Domain\PayrollReport\Repository\PayrollReportRepositoryInterface;
use App\Domain\PayrollReport\ValueObject\AttendanceCount;
use App\Domain\PayrollReport\ValueObject\AttendanceSummaryRef;
use App\Domain\PayrollReport\ValueObject\ComplianceCount;
use App\Domain\PayrollReport\ValueObject\DeductionBreakdown;
use App\Domain\PayrollReport\ValueObject\EmployeeSnapshot;
use App\Domain\PayrollReport\ValueObject\LeaveStatistic;
use App\Domain\PayrollReport\ValueObject\Name;
use App\Domain\PayrollReport\ValueObject\Notes;
use App\Domain\PayrollReport\ValueObject\OvertimeItem;
use App\Domain\PayrollReport\ValueObject\OvertimeSummary;
use App\Domain\PayrollReport\ValueObject\PayrollNet;
use App\Domain\PayrollReport\ValueObject\PayrollSummary;
use App\Domain\PayrollReport\ValueObject\ScheduleCount;
use App\Domain\PayrollReport\ValueObject\TimeStatistic;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\Enums\PayrollRateType;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Exception\BadRequestException;

/**
 * PayrollReport Domain Service
 *
 * Owns the auto-generation flow that:
 * 1. Resolves AttendanceSummary records for the period.
 * 2. For each summary, auto-generates PayrollDeduction records from attendance
 *    incidents (LATE, ABSENT, EARLY_OUT, MISSING_IN, MISSING_OUT) using
 *    PayrollRatePolicies configuration.
 * 3. Builds the PayrollSummary (including deduction block from PayrollDeduction
 *    records) and persists the PayrollReport.
 */
final class PayrollReportDomainService
{
    use DomainValidator;

    public function __construct(
        private DetailInfoFactory $detailInfoFactory,
        private PayrollDeductionDetailInfoFactory $deductionInfoFactory,
        private PayrollReportRepositoryInterface $repository,
        private AttendanceSummaryRepositoryInterface $attendanceSummaryRepository,
        private PayrollRatePoliciesRepositoryInterface $ratePolicyRepository,
        private PayrollDeductionRepositoryInterface $deductionRepository,
    ) {}

    public function generateAllFromAttendanceSummary(GeneratePayrollReportCommand $command): PayrollReportResult
    {
        $attendanceSummaries = $this->resolveAttendanceSummaries($command);
        $fineRateMap = $this->loadFineRateMap();

        $status = ResourceStatus::active();
        $detailInfo = $this->detailInfoFactory
            ->create(detailInfo: $command->detailInfo ?? [])
            ->build();

        $created = 0;
        $skipped = 0;
        $updated = 0;
        $errors = [];

        foreach ($attendanceSummaries as $attendanceSummary) {
            $summaryId = $attendanceSummary->getId();

            if ($summaryId === null) {
                continue;
            }

            try {
                // 1. Auto-generate PayrollDeduction records from attendance incidents.
                $this->generatePayrollDeductionsFromSummary(
                    attendanceSummary: $attendanceSummary,
                    fineRateMap: $fineRateMap,
                );

                // 2. Build payroll summary (deduction block from PayrollDeduction records).
                $payrollSummary = $this->calculatePayrollSummary(
                    attendanceSummary: $attendanceSummary,
                    fineRateMap: $fineRateMap,
                );

                // 2b. Build overtime summary (per-day items + totals).
                $overtimeSummary = $this->calculateOvertimeSummary($attendanceSummary);

                // 2c. Build payroll net (overtime - deduction).
                $payrollNet = PayrollNet::fromValues(
                    totalOvertime: $overtimeSummary->getTotalAmount(),
                    totalDeduction: $payrollSummary->getDeduction()->getTotalAmount(),
                );

                // 3. Upsert PayrollReport.
                $existingReport = $this->repository->findByAttendanceSummaryId($summaryId);

                if ($existingReport !== null) {
                    $existingReport->updatePayrollSummary($payrollSummary);
                    $existingReport->updateOvertimeSummary($overtimeSummary);
                    $existingReport->updatePayrollNet($payrollNet);
                    $existingReport->updateEmployee(
                        EmployeeSnapshot::fromArray($attendanceSummary->getEmployee()->toArray()),
                    );
                    $existingReport->updateAttendanceSummary(
                        AttendanceSummaryRef::fromValues(
                            id: $summaryId,
                            name: $attendanceSummary->getName(),
                        ),
                    );
                    $this->repository->update($existingReport);
                    $updated++;
                    continue;
                }

                $tempName = sprintf('PAYROLL-TEMP-%d-%d', $summaryId, $command->period);

                $entity = PayrollReport::create(
                    name: $tempName,
                    period: $command->period,
                    employee: EmployeeSnapshot::fromArray($attendanceSummary->getEmployee()->toArray()),
                    attendanceSummary: AttendanceSummaryRef::fromValues(
                        id: $summaryId,
                        name: $attendanceSummary->getName(),
                    ),
                    payrollSummary: $payrollSummary,
                    overtimeSummary: $overtimeSummary,
                    payrollNet: $payrollNet,
                    status: $status,
                    detailInfo: $detailInfo,
                    notes: Notes::fromNullable($command->notes),
                );

                $inserted = $this->repository->insert($entity);

                $finalName = Name::fromIdAndPeriod(
                    id: $inserted->getId() ?? 0,
                    period: $command->period,
                )->toString();

                $inserted->updateName(newName: $finalName);
                $this->repository->update($inserted);
                $created++;
            } catch (BadRequestException $e) {
                $skipped++;
            } catch (\Throwable $e) {
                $errors[$summaryId] = $e->getMessage();
            }
        }

        return PayrollReportResult::create(
            created: $created,
            skipped: $skipped,
            updated: $updated,
            errors: $errors,
        );
    }

    /**
     * Auto-generates PayrollDeduction records from attendance incidents,
     * broken down per day per incident type.
     *
     * Iterates the AttendanceSummary's per-day attendance data and creates
     * one PayrollDeduction record for each incident on each day:
     *   - LATE        when detail['late'] is set
     *   - EARLY_OUT   when detail['early_out'] > 0
     *   - MISSING_IN  when detail['missing_in'] is set
     *   - MISSING_OUT when detail['missing_out'] is set
     *   - ABSENT      when an obligation day has no start_time
     *
     * Note format: "{TYPE} {YYYY-MM-DD}" e.g. "LATE 2026-08-15".
     *
     * Idempotent: existing auto-generated deductions are matched by
     * incident type + date (extracted from the note). Stale records
     * (no longer matching any incident) are left as-is; only matching
     * records are created or updated.
     */
    private function generatePayrollDeductionsFromSummary(
        AttendanceSummary $attendanceSummary,
        array $fineRateMap,
    ): void {
        $employeeId = $attendanceSummary->getEmployee()->getEmployeeId();
        $period = $attendanceSummary->getPeriod();

        if ($employeeId === null || $period === null) {
            return;
        }

        // Collect per-day incidents from attendance data.
        $dailyIncidents = $this->collectDailyIncidents($attendanceSummary);

        if ($dailyIncidents === []) {
            return;
        }

        // Load existing PayrollDeduction records for this employee+period.
        $existingDeductions = $this->deductionRepository->findByEmployeeAndPeriod(
            employeeId: $employeeId,
            period: $period,
        );

        // Index existing auto-generated deductions by "TYPE|DATE".
        // Note format: "LATE 2026-08-15" → key = "LATE|2026-08-15"
        $existingByKey = [];
        foreach ($existingDeductions as $deduction) {
            $note = $deduction->getNote() ?? '';
            if (preg_match('/^([A-Z_]+)\s+(\d{4}-\d{2}-\d{2})$/', $note, $m)) {
                $existingByKey[$m[1] . '|' . $m[2]] = $deduction;
            }
        }

        $status = ResourceStatus::active();

        foreach ($dailyIncidents as $key => $incident) {
            $rateTypeStr = $incident['type'];
            $date = $incident['date'];

            $rate = $fineRateMap[$rateTypeStr] ?? null;
            if ($rate === null) {
                continue;
            }

            $amount = $rate->getAmount();
            if ($amount <= 0) {
                continue;
            }

            $deductionTypeId = $rate->getDeductionTypeId();
            if ($deductionTypeId === null || $deductionTypeId < 1) {
                // Deduction rate type must reference a PayrollDeductionType.
                // Skip if misconfigured (no deduction_type_id set).
                continue;
            }

            $note = sprintf('%s %s', $rateTypeStr, $date);

            $existing = $existingByKey[$key] ?? null;

            if ($existing !== null) {
                // Update amount if changed.
                if ($existing->getAmount() !== $amount) {
                    $existing->setAmount($amount);
                    $existing->setNote($note);

                    // Rebuild enriched detail_info (fixes legacy records that
                    // only have change_log without employee/deduction_type).
                    $info = $this->deductionInfoFactory->buildFromIds(
                        employeeId: $employeeId,
                        deductionTypeId: $deductionTypeId,
                        employeeDebtId: null,
                    );
                    $detailInfo = $this->detailInfoFactory
                        ->update(
                            detailInfo: $existing->getDetailInfo(),
                            payload: $info->toArray(),
                        )
                        ->build();
                    $existing->updateDetailInfo(detailInfo: $detailInfo);

                    $this->deductionRepository->update($existing);
                }
                continue;
            }

            // Create new PayrollDeduction.
            // Build enriched detail_info with employee + payroll_deduction_type
            // relations (mirrors the manual create flow in
            // PayrollDeductionApplicationService::create).
            $info = $this->deductionInfoFactory->buildFromIds(
                employeeId: $employeeId,
                deductionTypeId: $deductionTypeId,
                employeeDebtId: null,
            );

            $detailInfo = $this->detailInfoFactory
                ->create(detailInfo: $info->toArray())
                ->build();

            $deduction = PayrollDeduction::create(
                name: DeductionName::generate($employeeId)->toString(),
                employeeId: $employeeId,
                deductionTypeId: $deductionTypeId,
                employeeDebtId: null,
                period: $period,
                amount: $amount,
                note: $note,
                status: $status,
                detailInfo: $detailInfo,
            );

            $this->deductionRepository->insert($deduction);
        }
    }

    /**
     * Collects per-day incidents from the AttendanceSummary's daily attendance
     * data.
     *
     * @return array<string, array{type: string, date: string}> Keyed by "TYPE|DATE"
     */
    private function collectDailyIncidents(AttendanceSummary $attendanceSummary): array
    {
        $incidents = [];

        foreach ($attendanceSummary->getAttendance()->toArray() as $daily) {
            $date = (string) ($daily['date'] ?? '');
            if ($date === '') {
                continue;
            }

            $detail = $daily['info']['detail'] ?? [];
            $isPresent = !empty($daily['start_time']);
            $isObligationDay = (bool) ($daily['is_obligation_day'] ?? false);

            if (!empty($detail['late'])) {
                $incidents[PayrollRateType::LATE->value . '|' . $date] = [
                    'type' => PayrollRateType::LATE->value,
                    'date' => $date,
                ];
            }

            $earlyOut = (int) ($detail['early_out'] ?? 0);
            if ($earlyOut > 0) {
                $incidents[PayrollRateType::EARLY_OUT->value . '|' . $date] = [
                    'type' => PayrollRateType::EARLY_OUT->value,
                    'date' => $date,
                ];
            }

            if (!empty($detail['missing_in'])) {
                $incidents[PayrollRateType::MISSING_IN->value . '|' . $date] = [
                    'type' => PayrollRateType::MISSING_IN->value,
                    'date' => $date,
                ];
            }

            if (!empty($detail['missing_out'])) {
                $incidents[PayrollRateType::MISSING_OUT->value . '|' . $date] = [
                    'type' => PayrollRateType::MISSING_OUT->value,
                    'date' => $date,
                ];
            }

            // ABSENT: obligation day with no presence.
            if ($isObligationDay && !$isPresent) {
                $incidents[PayrollRateType::ABSENT->value . '|' . $date] = [
                    'type' => PayrollRateType::ABSENT->value,
                    'date' => $date,
                ];
            }
        }

        return $incidents;
    }

    /**
     * @return list<AttendanceSummary>
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
     * @return array<string, PayrollRatePolicies>
     */
    private function loadFineRateMap(): array
    {
        $map = [];
        foreach (PayrollRateType::cases() as $rateType) {
            $rate = $this->ratePolicyRepository->findByRateType($rateType);
            if ($rate !== null) {
                $map[$rateType->value] = $rate;
            }
        }
        return $map;
    }

    /**
     * Builds the PayrollSummary from an AttendanceSummary and the
     * PayrollDeduction records for the same employee+period.
     *
     * Overtime is NOT included here - it lives in the separate
     * overtime_summary field (see calculateOvertimeSummary).
     */
    private function calculatePayrollSummary(
        AttendanceSummary $attendanceSummary,
        array $fineRateMap,
    ): PayrollSummary {
        $sourceSummary = $attendanceSummary->getSummary();
        $sourceSchedule = $sourceSummary->getSchedule();
        $sourceAttendance = $sourceSummary->getAttendance();
        $sourceCompliance = $sourceSummary->getCompliance();
        $sourceTime = $sourceSummary->getTime();
        $sourceLeave = $sourceSummary->getLeave();

        $time = TimeStatistic::fromValues(
            lateCount: $sourceTime->getLateCount(),
            earlyOutCount: $sourceTime->getEarlyOutCount(),
            incompleteCount: $sourceTime->getIncomplete(),
        );

        // Build deduction block from PayrollDeduction records.
        $deduction = $this->buildDeductionBreakdown($attendanceSummary);

        return PayrollSummary::fromValues(
            schedule: ScheduleCount::fromValues(
                hekDays: $sourceSchedule->getHekDays(),
                obligationDays: $sourceSchedule->getObligationDays(),
            ),
            attendance: AttendanceCount::fromValues(
                presentDays: $sourceAttendance->getPresentDays(),
                absentDays: $sourceAttendance->getAbsentDays(),
                sickDays: $sourceAttendance->getSickDays(),
                leaveDays: $sourceAttendance->getLeaveDays(),
            ),
            compliance: ComplianceCount::fromValues(
                fulfilled: $sourceCompliance->getFulfilled(),
                unfulfilled: $sourceCompliance->getUnfulfilled(),
                deficit: $sourceCompliance->getDeficit(),
                excess: $sourceCompliance->getExcess(),
                pendingReview: $sourceCompliance->getPendingReview(),
            ),
            time: $time,
            leave: LeaveStatistic::fromValues(
                quotaUsed: $sourceLeave->getQuotaUsed(),
            ),
            deduction: $deduction,
        );
    }

    /**
     * Builds the OvertimeSummary from an AttendanceSummary's per-day overtime
     * items. Each item gets an amount = minutes × OVERTIME rate from
     * PayrollRatePolicies.
     */
    private function calculateOvertimeSummary(AttendanceSummary $attendanceSummary): OvertimeSummary
    {
        $sourceOvertime = $attendanceSummary->getSummary()->getOvertime();

        // Load OVERTIME rate from PayrollRatePolicies (DB-based, not hardcoded).
        $overtimePolicy = $this->ratePolicyRepository->findByRateType(
            PayrollRateType::OVERTIME,
        );
        $rate = $overtimePolicy?->getAmount() ?? 0;

        $items = [];
        $totalAmount = 0;

        foreach ($sourceOvertime->getItems() as $item) {
            $minutes = $item['minutes'] ?? 0;
            $amount = $minutes * $rate;

            $items[] = OvertimeItem::fromValues(
                date: $item['date'] ?? '',
                minutes: $minutes,
                amount: $amount,
            );

            $totalAmount += $amount;
        }

        return OvertimeSummary::fromValues(
            items: $items,
            totalAmount: $totalAmount,
        );
    }

    /**
     * Builds the DeductionBreakdown from PayrollDeduction records for the
     * employee+period of the given AttendanceSummary.
     */
    private function buildDeductionBreakdown(AttendanceSummary $attendanceSummary): DeductionBreakdown
    {
        $employeeId = $attendanceSummary->getEmployee()->getEmployeeId();
        $period = $attendanceSummary->getPeriod();

        if ($employeeId === null || $period === null) {
            return DeductionBreakdown::empty();
        }

        $deductions = $this->deductionRepository->findByEmployeeAndPeriod(
            employeeId: $employeeId,
            period: $period,
        );

        $items = [];
        $totalAmount = 0;

        foreach ($deductions as $deduction) {
            $items[] = [
                'id' => $deduction->getId(),
                'name' => $deduction->getName(),
                'deduction_type_id' => $deduction->getDeductionTypeId(),
                'amount' => $deduction->getAmount(),
                'note' => $deduction->getNote(),
            ];
            $totalAmount += $deduction->getAmount();
        }

        return DeductionBreakdown::fromValues(
            totalAmount: $totalAmount,
            items: $items,
        );
    }
}
