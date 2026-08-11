<?php

declare(strict_types=1);

namespace App\Domain\AttendanceSummary\Repository;

// Domain Layer
use App\Domain\AttendanceSummary\Entity\AttendanceSummary;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * AttendanceSummary Repository Interface
 * 
 * Pure repository pattern for AttendanceSummary aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface AttendanceSummaryRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?AttendanceSummary;

    public function findByName(string $name, ?int $status = null): ?AttendanceSummary;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(AttendanceSummary $AttendanceSummary): AttendanceSummary;

    public function update(AttendanceSummary $AttendanceSummary): AttendanceSummary;

    public function delete(AttendanceSummary $AttendanceSummary): AttendanceSummary;

    public function restore(int $id): ?AttendanceSummary;

}
