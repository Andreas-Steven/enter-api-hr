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
     * @param array<int, array> $attendanceDetail
     * @return array{0: array, 1: array}
     */
    private function calculateSummaries(array $attendanceDetail): array
    {
        $hekCount = count($attendanceDetail);
        $presentDays = 0;
        $validPresentCount = 0;
        $lateCount = 0;
        $lateMinTotal = 0;
        $earlyOutCount = 0;
        $earlyOutMinTotal = 0;
        $incompleteCount = 0;

        foreach ($attendanceDetail as $entry) {
            $info = $entry['info'] ?? [];
            $detail = $info['detail'] ?? [];

            if ($info['in'] ?? false) {
                $presentDays++;
            }

            if ($detail['full_day'] ?? false || $detail['half_day'] ?? false) {
                $validPresentCount++;
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
        }

        $periodSummary = [
            'present_days' => $presentDays,
            'absent_days' => 0,
            'leave_days' => 0,
            'sick_days' => 0,
            'permission_days' => 0,
            'hek_count' => $hekCount,
            'valid_present_count' => $validPresentCount,
            'deficit_count' => max(0, $hekCount - $validPresentCount),
            'excess_count' => 0,
            'overtime_day_count' => 0,
            'leave_quota_used_count' => 0,
            'deduction_day_count' => $incompleteCount,
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
}
