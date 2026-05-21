<?php

declare(strict_types=1);

namespace App\Domain\AttendancePolicies\Repository;

// Domain Layer
use App\Domain\AttendancePolicies\Entity\AttendancePolicies;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * AttendancePolicies Repository Interface
 * 
 * Pure repository pattern for AttendancePolicies aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface AttendancePoliciesRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?AttendancePolicies;

    public function findByName(string $name, ?int $status = null): ?AttendancePolicies;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(AttendancePolicies $attendancePolicies): AttendancePolicies;

    public function update(AttendancePolicies $attendancePolicies): AttendancePolicies;

    public function delete(AttendancePolicies $attendancePolicies): AttendancePolicies;

    public function restore(int $id): ?AttendancePolicies;

}
