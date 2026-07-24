<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReport\Repository;

// Domain Layer
use App\Domain\AttendanceReport\Entity\AttendanceReport;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * AttendanceReport Repository Interface
 * 
 * Pure repository pattern for AttendanceReport aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface AttendanceReportRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?AttendanceReport;

    public function findByName(string $name, ?int $status = null): ?AttendanceReport;

    public function findByPeriodAndEmployeeId(int $period, int $employeeId, ?int $status = null): ?AttendanceReport;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(AttendanceReport $attendanceReport): AttendanceReport;

    public function update(AttendanceReport $attendanceReport): AttendanceReport;

    public function delete(AttendanceReport $attendanceReport): AttendanceReport;

    public function restore(int $id): ?AttendanceReport;

}
