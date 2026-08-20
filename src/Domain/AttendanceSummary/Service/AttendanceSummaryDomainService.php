<?php

declare(strict_types=1);

namespace App\Domain\AttendanceSummary\Service;

// Application Layer
use App\Application\AttendanceSummary\Command\GenerateAttendanceSummaryCommand;
use App\Application\AttendanceSummary\Dto\AttendanceSummaryResult;
use App\Application\Shared\Factory\DetailInfoFactory;
// Domain Layer
use App\Domain\Attendance\Entity\Attendance;
use App\Domain\Attendance\Repository\AttendanceRepositoryInterface;
use App\Domain\Attendance\ValueObject\AttendanceItem;
use App\Domain\AttendanceCalendar\Repository\AttendanceCalendarRepositoryInterface;
use App\Domain\AttendancePermission\Entity\AttendancePermission;
use App\Domain\AttendancePermission\Repository\AttendancePermissionRepositoryInterface;
use App\Domain\AttendanceSummary\Entity\AttendanceSummary;
use App\Domain\AttendanceSummary\Repository\AttendanceSummaryRepositoryInterface;
use App\Domain\AttendanceSummary\ValueObject\AttendanceData;
use App\Domain\AttendanceSummary\ValueObject\EmployeeSnapshot;
use App\Domain\AttendanceSummary\ValueObject\Name;
use App\Domain\AttendanceSummary\ValueObject\Notes;
use App\Domain\AttendanceSummary\ValueObject\Period;
use App\Domain\AttendanceSummary\ValueObject\PermissionCollection;
use App\Domain\AttendanceSummary\ValueObject\Policy;
use App\Domain\AttendanceSummary\ValueObject\Summary;
use App\Domain\AttendanceSummary\ValueObject\WorkSchedule;
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\Enums\ApprovalStatus;
use App\Domain\Shared\ValueObject\ResourceStatus;
// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\BadRequestException;

/**
 * AttendanceSummary Domain Service
 *
 * Pure business logic and domain rules. Owns the auto-generation flow that
 * folds an employee's daily attendance (together with their work-schedule
 * rules) into one AttendanceSummary per employee per period.
 */
final class AttendanceSummaryDomainService
{
    use DomainValidator;

    public function __construct(
        private DetailInfoFactory $detailInfoFactory,
        private AttendanceSummaryRepositoryInterface $repository,
        private AttendanceRepositoryInterface $attendanceRepository,
        private EmployeeRepositoryInterface $employeeRepository,
        private AttendancePermissionRepositoryInterface $permissionRepository,
        private AttendanceCalendarRepositoryInterface $calendarRepository,
    ) {}

    /**
     * Orchestrates the full AttendanceSummary generation flow for a period:
     * resolves the active employees, loads their attendance, computes every
     * calculated field via the Summary Value Object and persists the result.
     *
     * Re-running for the same period is idempotent: summaries that already
     * exist for an (employee, period) are kept untouched (counted as skipped).
     */
    public function generateAllFromAttendance(GenerateAttendanceSummaryCommand $command): AttendanceSummaryResult
    {
        $periodVo = Period::fromNullable($command->period);
        $periodDays = array_values(iterator_to_array($periodVo->iterateDays()));

        $employees = $this->resolveEmployees($command);
        $attendances = $this->attendanceRepository->findByPeriod($command->period);

        // Load holidays for the period (shared across all employees).
        $holidays = $this->calendarRepository->findByPeriod($command->period);

        [$dailyByEmployee, $rawByEmployee] = $this->groupAttendanceByEmployee($attendances);

        // New summaries are always ACTIVE; older period summaries are marked COMPLETE.
        $status = ResourceStatus::active();
        $detailInfo = $this->detailInfoFactory
            ->create(detailInfo: $command->detailInfo ?? [])
            ->build();

        // Mark summaries from previous periods as COMPLETE.
        $this->markPreviousPeriodSummariesAsCompleted($command->period);

        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($employees as $employee) {
            $employeeId = $employee->getId();

            if ($employeeId === null) {
                continue;
            }

            try {
                $name = Name::fromString(sprintf('SUMMARY-EK%d-%d', $employeeId, $command->period));

                // Keep existing summaries untouched (idempotent re-generation).
                if ($this->repository->existsByName($name->toString())) {
                    $skipped++;
                    continue;
                }

                $dailyItems = $dailyByEmployee[$employeeId] ?? [];
                $rawDaily = $rawByEmployee[$employeeId] ?? [];

                // Load approved permissions for this employee in the period.
                $permissionEntities = $this->permissionRepository->findByEmployeeAndPeriod(
                    employeeId: $employeeId,
                    period: $command->period,
                );
                [$permissionItems, $permissionDateMap] = $this->buildPermissionSnapshot(
                    $permissionEntities,
                );

                $employeeSnapshot = EmployeeSnapshot::fromEmployee($employee);
                $workSchedule = WorkSchedule::fromEmployee($employee);
                $policy = Policy::fromDailyItems($dailyItems);

                // Filter holidays by the employee's branch scope.
                $holidayDates = $this->filterHolidaysByBranch($holidays, $employee->getBranchId());

                // Build attendance data with correct is_obligation_day flags.
                // Period days and permission date map are passed so that absent
                // obligation days (no attendance record, no approved leave/sick)
                // are injected into the attendance array — enabling downstream
                // ABSENT deduction generation in PayrollReport.
                $attendanceData = AttendanceData::fromRawDailyItems(
                    $rawDaily,
                    $workSchedule,
                    $holidayDates,
                    $periodDays,
                    $permissionDateMap,
                );
                $permissions = PermissionCollection::fromArray($permissionItems);
                $summary = Summary::compute(
                    workSchedule: $workSchedule,
                    dailyItems: $dailyItems,
                    periodDays: $periodDays,
                    permissionDateMap: $permissionDateMap,
                    holidayDates: $holidayDates,
                    policy: $policy,
                );

                $entity = AttendanceSummary::create(
                    name: $name,
                    employee: $employeeSnapshot,
                    workSchedule: $workSchedule,
                    attendance: $attendanceData,
                    permissions: $permissions,
                    summary: $summary,
                    policy: $policy,
                    status: $status,
                    detailInfo: $detailInfo,
                    period: $periodVo,
                    notes: Notes::fromNullable($command->notes),
                );

                $this->repository->insert(entity: $entity);
                $created++;
            } catch (BadRequestException $e) {
                // Domain rule violation (e.g. invalid generated name) => skip.
                $skipped++;
            } catch (\Throwable $e) {
                $errors[$employeeId] = $e->getMessage();
            }
        }

        return AttendanceSummaryResult::create(
            created: $created,
            skipped: $skipped,
            errors: $errors,
        );
    }

    /**
     * Resolves the employees to generate for.
     *
     * When employeeId is supplied (and > 0) only that employee is used;
     * otherwise every active employee is loaded.
     *
     * @return list<\App\Domain\Employee\Entity\Employee>
     */
    private function resolveEmployees(GenerateAttendanceSummaryCommand $command): array
    {
        if ($command->employeeId !== null && $command->employeeId > 0) {
            $employee = $this->employeeRepository->findById($command->employeeId);
            return $employee === null ? [] : [$employee];
        }

        return $this->employeeRepository->findAllActive();
    }

    /**
     * Groups the period's attendance into per-employee lookups.
     *
     * Returns two maps keyed by employee id:
     *  - daily: date => AttendanceItem (normalized, used for calculation)
     *  - raw:   date => raw item array (preserves full info, used for the
     *    attendance detail snapshot)
     *
     * @param list<Attendance> $attendances
     *
     * @return array{0: array<int, array<string, AttendanceItem>>, 1: array<int, array<string, array>>}
     */
    private function groupAttendanceByEmployee(array $attendances): array
    {
        $dailyByEmployee = [];
        $rawByEmployee = [];

        foreach ($attendances as $attendance) {
            $date = $attendance->getDate();

            foreach ($attendance->getAttendance() as $rawItem) {
                if (!is_array($rawItem) || !isset($rawItem['employee_id'])) {
                    continue;
                }

                $employeeId = (int) $rawItem['employee_id'];
                $rawByEmployee[$employeeId][$date] = $rawItem;

                try {
                    $dailyByEmployee[$employeeId][$date] = AttendanceItem::fromArray($rawItem);
                } catch (\Throwable) {
                    // Keep the raw entry for the snapshot even if normalization
                    // fails; the summary simply treats that day as unprocessed.
                }
            }
        }

        return [$dailyByEmployee, $rawByEmployee];
    }

    /**
     * Builds the permission snapshot array and a flat date => permission-type
     * map from the employee's approved AttendancePermission records.
     *
     * Only approved permissions are included. The date map expands multi-day
     * permissions (start_date..end_date) into individual calendar days.
     *
     * @param AttendancePermission[] $permissions
     *
     * @return array{0: list<array<string, mixed>>, 1: array<string, int>}
     */
    private function buildPermissionSnapshot(array $permissions): array
    {
        $items = [];
        $dateMap = [];

        foreach ($permissions as $permission) {
            if ($permission->getApproved() !== ApprovalStatus::APPROVED->value) {
                continue;
            }

            $detailInfo = $permission->getDetailInfo();
            $totalDay = $detailInfo->get('total_day') ?? 1;

            $items[] = [
                'id' => $permission->getId(),
                'permission_type' => $permission->getPermissionType(),
                'start_date' => $permission->getStartDate(),
                'end_date' => $permission->getEndDate(),
                'total_day' => $totalDay,
                'approved' => $permission->getApproved(),
                'permission' => $detailInfo->get('permission'),
            ];

            // Expand the date range into individual days for the summary map.
            $startDate = $permission->getStartDate();
            $endDate = $permission->getEndDate() ?? $startDate;

            if ($startDate === null) {
                continue;
            }

            try {
                $current = new \DateTimeImmutable($startDate);
                $end = new \DateTimeImmutable($endDate);
            } catch (\Throwable) {
                continue;
            }

            while ($current <= $end) {
                $dateStr = $current->format('Y-m-d');
                $dateMap[$dateStr] = $permission->getPermissionType();
                $current = $current->modify('+1 day');
            }
        }

        return [$items, $dateMap];
    }

    /**
     * Filters holidays to only those applicable to the employee's branch.
     *
     * Returns a map of date (Y-m-d) => day_off value for holidays whose scope
     * includes the given branch id.
     *
     * @param array<int, \App\Domain\AttendanceCalendar\Entity\AttendanceCalendar> $holidays
     *
     * @return array<string, int>
     */
    private function filterHolidaysByBranch(array $holidays, ?int $branchId): array
    {
        if ($branchId === null) {
            return [];
        }

        $result = [];
        foreach ($holidays as $holiday) {
            $scopeBranchIds = $holiday->getScope()->getBranchIds();
            if (!in_array($branchId, $scopeBranchIds, true)) {
                continue;
            }
            $result[$holiday->getDate()] = $holiday->getDayOff();
        }

        return $result;
    }

    /**
     * Marks all AttendanceSummary records from periods strictly older than the
     * given period as COMPLETE, so only the current period remains ACTIVE.
     */
    private function markPreviousPeriodSummariesAsCompleted(int $currentPeriod): void
    {
        // Load all ACTIVE summaries (status = 1) across every period.
        $criteria = new SearchCriteria(
            page: 1,
            pageSize: 10000,
            filter: ['status' => 1],
            sortBy: 'id',
            sortDir: 'asc',
        );

        $result = $this->repository->list($criteria);

        foreach ($result->data as $row) {
            $rowPeriod = (int) ($row['period'] ?? 0);
            if ($rowPeriod >= $currentPeriod) {
                continue;
            }

            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $existing = $this->repository->findById($id);
            if ($existing === null) {
                continue;
            }

            $existing->applyStatus(ResourceStatus::completed());
            $this->repository->update($existing);
        }
    }
}
