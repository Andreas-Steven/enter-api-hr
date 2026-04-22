<?php

declare(strict_types=1);

namespace App\Domain\AttendancePermission\Repository;

// Domain Layer
use App\Domain\AttendancePermission\Entity\AttendancePermission;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * AttendancePermission Repository Interface
 * 
 * Pure repository pattern for AttendancePermission aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface AttendancePermissionRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?AttendancePermission;

    public function findByName(string $name, ?int $status = null): ?AttendancePermission;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(AttendancePermission $attendancePermission): AttendancePermission;

    public function update(AttendancePermission $attendancePermission): AttendancePermission;

    public function delete(AttendancePermission $attendancePermission): AttendancePermission;

    public function restore(int $id): ?AttendancePermission;

}
