<?php

declare(strict_types=1);

namespace App\Domain\PayrollReport\Service;

// Application Layer
use App\Application\PayrollReport\Command\GeneratePayrollReportCommand;
use App\Application\PayrollReport\Dto\PayrollReportResult;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\AttendanceSummary\Entity\AttendanceSummary;
use App\Domain\AttendanceSummary\Repository\AttendanceSummaryRepositoryInterface;
use App\Domain\AttendanceFineRate\Entity\AttendanceFineRate;
use App\Domain\AttendanceFineRate\Repository\AttendanceFineRateRepositoryInterface;
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
use App\Domain\PayrollReport\ValueObject\OvertimeStatistic;
use App\Domain\PayrollReport\ValueObject\PayrollSummary;
use App\Domain\PayrollReport\ValueObject\ScheduleCount;
use App\Domain\PayrollReport\ValueObject\TimeStatistic;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\Enums\IncidentType;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Exception\BadRequestException;

/**
 * PayrollReport Domain Service
 *
 * Pure business logic and domain rules. Owns the auto-generation flow that
 * folds an employee's AttendanceSummary together with the configured
 * AttendanceFineRate deduction rates into one PayrollReport per employee per
 * period.
 */
final class PayrollReportDomainService
{
    use DomainValidator;

    public function __construct(
        private DetailInfoFactory $detailInfoFactory,
        private PayrollReportRepositoryInterface $repository,
        private AttendanceSummaryRepositoryInterface $attendanceSummaryRepository,
        private AttendanceFineRateRepositoryInterface $fineRateRepository,
    ) {}

    /**
     * Orchestrates the full PayrollReport generation flow for a period:
     * resolves the source AttendanceSummary records, builds the PayrollSummary
     * (including deduction calculation from AttendanceFineRate), and persists
     * the result.
     *
     * Re-running for the same period is idempotent: reports that already exist
     * for an AttendanceSummary are updated (recalculated) instead of duplicated.
     */
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
                $payrollSummary = $this->calculatePayrollSummary(
                    attendanceSummary: $attendanceSummary,
                    fineRateMap: $fineRateMap,
                );

                $existingReport = $this->repository->findByAttendanceSummaryId($summaryId);

                if ($existingReport !== null) {
                    $existingReport->updatePayrollSummary($payrollSummary);
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
                    status: $status,
                    detailInfo: $detailInfo,
                    notes: Notes::fromNullable($command->notes),
                );

                $inserted = $this->repository->insert($entity);

                // Update the name with the generated id: PAYROLL-{id}-{period}
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
     * Resolves the source AttendanceSummary records for generation.
     *
     * If attendanceSummaryId is provided (and > 0), only that summary is used.
     * Otherwise, all summaries for the given period are loaded.
     *
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
     * Loads all active AttendanceFineRate records and indexes them by
     * incident type for fast lookup during deduction calculation.
     *
     * @return array<string, AttendanceFineRate>
     */
    private function loadFineRateMap(): array
    {
        $map = [];
        foreach (IncidentType::cases() as $incidentType) {
            $rate = $this->fineRateRepository->findByIncidentType($incidentType);
            if ($rate !== null) {
                $map[$incidentType->value] = $rate;
            }
        }
        return $map;
    }

    /**
     * Builds the PayrollSummary Value Object from an AttendanceSummary and the
     * configured fine rates.
     *
     * The schedule, attendance, compliance, overtime, and leave blocks are
     * sourced directly from the AttendanceSummary's calculated summary. The
     * time block is a simplified version (counts only, no minutes). The
     * deduction block is calculated by multiplying each incident count by the
     * corresponding AttendanceFineRate amount.
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
        $sourceOvertime = $sourceSummary->getOvertime();
        $sourceLeave = $sourceSummary->getLeave();

        // Count individual missing_in / missing_out from the attendance data.
        [$missingInCount, $missingOutCount] = $this->countMissingInOut($attendanceSummary);

        // Build the simplified time statistic (counts only).
        $time = TimeStatistic::fromValues(
            lateCount: $sourceTime->getLateCount(),
            earlyOutCount: $sourceTime->getEarlyOutCount(),
            incompleteCount: $sourceTime->getIncomplete(),
        );

        // Build the deduction breakdown.
        $deduction = $this->calculateDeduction(
            lateCount: $sourceTime->getLateCount(),
            absentDays: $sourceAttendance->getAbsentDays(),
            earlyOutCount: $sourceTime->getEarlyOutCount(),
            missingInCount: $missingInCount,
            missingOutCount: $missingOutCount,
            fineRateMap: $fineRateMap,
        );

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
            overtime: OvertimeStatistic::fromValues(
                days: $sourceOvertime->getDays(),
                minutes: $sourceOvertime->getMinutes(),
            ),
            leave: LeaveStatistic::fromValues(
                quotaUsed: $sourceLeave->getQuotaUsed(),
            ),
            deduction: $deduction,
        );
    }

    /**
     * Counts individual missing_in and missing_out occurrences from the
     * AttendanceSummary's daily attendance data.
     *
     * The AttendanceSummary's TimeStatistic only stores a combined `incomplete`
     * count (missing_in OR missing_out). For the payroll deduction breakdown we
     * need the individual counts, so we re-iterate the daily attendance entries.
     *
     * @return array{0: int, 1: int} [missingInCount, missingOutCount]
     */
    private function countMissingInOut(AttendanceSummary $attendanceSummary): array
    {
        $missingInCount = 0;
        $missingOutCount = 0;

        foreach ($attendanceSummary->getAttendance()->toArray() as $daily) {
            $detail = $daily['info']['detail'] ?? [];

            if (!empty($detail['missing_in'])) {
                $missingInCount++;
            }
            if (!empty($detail['missing_out'])) {
                $missingOutCount++;
            }
        }

        return [$missingInCount, $missingOutCount];
    }

    /**
     * Calculates the deduction breakdown by multiplying each incident count
     * by the corresponding AttendanceFineRate amount.
     *
     * @param array<string, AttendanceFineRate> $fineRateMap
     */
    private function calculateDeduction(
        int $lateCount,
        int $absentDays,
        int $earlyOutCount,
        int $missingInCount,
        int $missingOutCount,
        array $fineRateMap,
    ): DeductionBreakdown {
        $breakdown = [];
        $totalAmount = 0;

        // LATE
        $lateAmount = $this->computeAmount($fineRateMap, IncidentType::LATE, $lateCount);
        $breakdown[IncidentType::LATE->value] = ['count' => $lateCount, 'amount' => $lateAmount];
        $totalAmount += $lateAmount;

        // ABSENT (measured in days, not count)
        $absentAmount = $this->computeAmount($fineRateMap, IncidentType::ABSENT, $absentDays);
        $breakdown[IncidentType::ABSENT->value] = ['days' => $absentDays, 'amount' => $absentAmount];
        $totalAmount += $absentAmount;

        // EARLY_OUT
        $earlyOutAmount = $this->computeAmount($fineRateMap, IncidentType::EARLY_OUT, $earlyOutCount);
        $breakdown[IncidentType::EARLY_OUT->value] = ['count' => $earlyOutCount, 'amount' => $earlyOutAmount];
        $totalAmount += $earlyOutAmount;

        // MISSING_IN
        $missingInAmount = $this->computeAmount($fineRateMap, IncidentType::MISSING_IN, $missingInCount);
        $breakdown[IncidentType::MISSING_IN->value] = ['count' => $missingInCount, 'amount' => $missingInAmount];
        $totalAmount += $missingInAmount;

        // MISSING_OUT
        $missingOutAmount = $this->computeAmount($fineRateMap, IncidentType::MISSING_OUT, $missingOutCount);
        $breakdown[IncidentType::MISSING_OUT->value] = ['count' => $missingOutCount, 'amount' => $missingOutAmount];
        $totalAmount += $missingOutAmount;

        return DeductionBreakdown::fromValues(
            totalAmount: $totalAmount,
            breakdown: $breakdown,
        );
    }

    /**
     * Computes the deduction amount for a given incident type and count.
     *
     * If no fine rate is configured for the incident type, the amount is 0.
     *
     * @param array<string, AttendanceFineRate> $fineRateMap
     */
    private function computeAmount(array $fineRateMap, IncidentType $type, int $count): int
    {
        $rate = $fineRateMap[$type->value] ?? null;
        if ($rate === null) {
            return 0;
        }
        return $count * $rate->getAmount();
    }
}
