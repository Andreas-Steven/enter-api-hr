<?php

declare(strict_types=1);

namespace App\Domain\AttendanceShift\Repository;

// Domain Layer
use App\Domain\AttendanceShift\Entity\AttendanceShift;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * AttendanceShift Repository Interface
 * 
 * Pure repository pattern for AttendanceShift aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface AttendanceShiftRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?AttendanceShift;

    public function findByName(string $name, ?int $status = null): ?AttendanceShift;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(AttendanceShift $attendanceShift): AttendanceShift;

    public function update(AttendanceShift $attendanceShift): AttendanceShift;

    public function delete(AttendanceShift $attendanceShift): AttendanceShift;

    public function restore(int $id): ?AttendanceShift;

}
