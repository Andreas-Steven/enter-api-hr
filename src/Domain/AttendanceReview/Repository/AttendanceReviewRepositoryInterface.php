<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReview\Repository;

// Domain Layer
use App\Domain\AttendanceReview\Entity\AttendanceReview;

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
     * @return list<AttendanceReview>
     */
    public function findByPeriod(int $period, ?int $status = null): array;

    public function findByAttendanceSummaryId(int $attendanceSummaryId, ?int $status = null): ?AttendanceReview;

    public function list(SearchCriteria $criteria): PaginatedResult;

    public function insert(AttendanceReview $entity): AttendanceReview;

    public function update(AttendanceReview $entity): AttendanceReview;

    public function delete(AttendanceReview $entity): AttendanceReview;

    public function restore(int $id): ?AttendanceReview;
}
