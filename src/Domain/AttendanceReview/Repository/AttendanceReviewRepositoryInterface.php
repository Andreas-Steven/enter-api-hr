<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReview\Repository;

// Domain Layer
use App\Domain\AttendanceReview\Entity\AttendanceReview;
use App\Domain\Shared\Enums\ApprovalStatus;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * AttendanceReview Repository Interface
 *
 * Pure repository pattern for AttendanceReview aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface AttendanceReviewRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?AttendanceReview;

    public function findByName(string $name, ?int $status = null): ?AttendanceReview;

    public function existsByName(string $name, ?int $status = null): bool;

    /**
     * Find a review item by its stable key (employee + period + source hash).
     *
     * Used by the keep-by-stable-key regeneration strategy to detect
     * whether a review item already exists for a given source.
     */
    public function findByStableKey(int $employeeId, int $period, string $stableKey): ?AttendanceReview;

    /**
     * Check whether a stable key already exists.
     */
    public function existsByStableKey(int $employeeId, int $period, string $stableKey): bool;

    /**
     * Find all review items for a given employee and period.
     *
     * @return array<int, AttendanceReview>
     */
    public function findByEmployeeAndPeriod(int $employeeId, int $period, ?ApprovalStatus $approvalStatus = null): array;

    /**
     * Find all review items linked to a specific AttendanceSummary.
     *
     * @return array<int, AttendanceReview>
     */
    public function findByAttendanceSummaryId(int $attendanceSummaryId, ?ApprovalStatus $approvalStatus = null): array;

    /**
     * Count pending (unresolved) review items for a given employee and period.
     */
    public function countPendingByEmployeeAndPeriod(int $employeeId, int $period): int;

    public function list(SearchCriteria $criteria): PaginatedResult;

    public function insert(AttendanceReview $attendanceReview): AttendanceReview;

    public function update(AttendanceReview $attendanceReview): AttendanceReview;

    public function delete(AttendanceReview $attendanceReview): AttendanceReview;

    public function restore(int $id): ?AttendanceReview;
}
