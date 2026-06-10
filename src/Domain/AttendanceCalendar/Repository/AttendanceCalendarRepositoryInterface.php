<?php

declare(strict_types=1);

namespace App\Domain\AttendanceCalendar\Repository;

// Domain Layer
use App\Domain\AttendanceCalendar\Entity\AttendanceCalendar;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * AttendanceCalendar Repository Interface
 * 
 * Pure repository pattern for AttendanceCalendar aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface AttendanceCalendarRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?AttendanceCalendar;

    public function findByName(string $name, ?int $status = null): ?AttendanceCalendar;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(AttendanceCalendar $attendanceCalendar): AttendanceCalendar;

    public function update(AttendanceCalendar $attendanceCalendar): AttendanceCalendar;

    public function delete(AttendanceCalendar $attendanceCalendar): AttendanceCalendar;

    public function restore(int $id): ?AttendanceCalendar;

}
