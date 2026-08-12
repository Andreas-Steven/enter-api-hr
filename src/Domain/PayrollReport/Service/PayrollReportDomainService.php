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
use App\Domain\PayrollReport\Entity\PayrollReport;
use App\Domain\PayrollReport\Repository\PayrollReportRepositoryInterface;
use App\Domain\PayrollReport\Result\GenerationResult;
use App\Domain\PayrollReport\ValueObject\Name;
use App\Domain\Shared\Concerns\Service\DomainValidator;
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
        $name = Name::fromAttendanceSummaryAndPeriod(
            attendanceSummaryId: $attendanceSummary->getId(),
            period: $period,
        )->getValue();

        $this->ensureUniqueName($name);

        $payrollSummary = $this->calculatePayrollSummary($attendanceSummary);

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

        foreach ($attendanceSummaries as $attendanceSummary) {
            try {
                $entities[] = $this->generateForAttendanceSummary(
                    attendanceSummary: $attendanceSummary,
                    period: $period,
                    notes: $notes,
                    detailInfo: $detailInfo,
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
     * Persists built PayrollReport entities and aggregates the outcome.
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
     * period_summary and time_summary.
     *
     * Mapping:
     * - obligation_total_count     ← period_summary.hek_count
     * - obligation_unfulfilled_count ← period_summary.deficit_count
     * - daily_anomaly_count        ← time_summary.incomplete_count
     * - daily_late_count           ← time_summary.late_count
     */
    private function calculatePayrollSummary(AttendanceSummary $attendanceSummary): array
    {
        $periodSummary = $attendanceSummary->getPeriodSummary();
        $timeSummary = $attendanceSummary->getTimeSummary();

        return [
            'obligation_total_count' => (int) ($periodSummary['hek_count'] ?? 0),
            'obligation_unfulfilled_count' => (int) ($periodSummary['deficit_count'] ?? 0),
            'daily_anomaly_count' => (int) ($timeSummary['incomplete_count'] ?? 0),
            'daily_late_count' => (int) ($timeSummary['late_count'] ?? 0),
        ];
    }
}
