<?php

declare(strict_types=1);

namespace App\Domain\AttendancePeriod\Repository;

// Domain Layer
use App\Domain\AttendancePeriod\Entity\AttendancePeriod;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * AttendancePeriod Repository Interface
 * 
 * Pure repository pattern for AttendancePeriod aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface AttendancePeriodRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?AttendancePeriod;

    public function findByName(string $name, ?int $status = null): ?AttendancePeriod;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(AttendancePeriod $attendancePeriod): AttendancePeriod;

    public function update(AttendancePeriod $attendancePeriod): AttendancePeriod;

    public function delete(AttendancePeriod $attendancePeriod): AttendancePeriod;

    public function restore(int $id): ?AttendancePeriod;

    public function upsert(AttendancePeriod $attendancePeriod): AttendancePeriod;
}
