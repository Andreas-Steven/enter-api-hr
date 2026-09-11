<?php

declare(strict_types=1);

namespace App\Application\AttendancePermission;

use App\Application\AttendancePermission\Dto\AttendancePermissionDetailInfo;

use App\Domain\Shared\Enums\PermissionType;
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;
use App\Domain\Attendance\Repository\AttendanceRepositoryInterface;

use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;

final readonly class AttendancePermissionDetailInfoFactory
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private AttendanceRepositoryInterface $attendanceRepository,
    ) {}

    public function buildFromPrimitives(
        ?int $employeeId = null,
        ?int $permissionId = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): AttendancePermissionDetailInfo {
        
        $permission = $this->buildPermissionArray($permissionId);
        $permissionType = $permissionId !== null ? PermissionType::tryFrom($permissionId) : null;
        
        // Calculate total_day
        $totalDay = null;
        if ($startDate !== null && $endDate !== null) {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $interval = $start->diff($end);
            $totalDay = $interval->days + 1; // Include both start and end dates
        } elseif ($startDate !== null) {
            $totalDay = 1; // Single day
        }
        
        // 1. Build Employee Info
        $employee = null;
        if ($employeeId !== null) {
            $employee = $this->employeeRepository->findById($employeeId);
        }

        $attendanceData = null;
        if ($permissionType !== null && $permissionType->requiresRevision() && $startDate !== null && $employeeId !== null) {
            $attendanceEntity = $this->attendanceRepository->findByDate($startDate);
            
            if ($attendanceEntity !== null) {
                $recordsCollection = $attendanceEntity->getAttendanceRecords();
                $specificItem = $recordsCollection->findByEmployeeId($employeeId);

                if ($specificItem !== null) {
                    $attendanceData = [
                        'id' => $attendanceEntity->getId(),
                        'date' => $attendanceEntity->getDate(),
                        'attendance' => $specificItem->toArray(), 
                    ];
                }
            }
        }

        return new AttendancePermissionDetailInfo(
            employee: $employee?->toPersistence(),
            permission: $permission,
            attendance: $attendanceData,
            totalDay: $totalDay,
        );
    }

    private function buildPermissionArray(?int $permissionId): ?array
    {
        if ($permissionId === null) return null;

        try {
            $permissionType = PermissionType::from($permissionId);
            return [
                'id' => $permissionType->value,
                'name' => $permissionType->getLabel(),
            ];
        } catch (\ValueError) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'attendance_permission.invalid_permission_id', 
                    params: [
                        'permission_id' => $permissionId,
                        'must_be' => implode(', ', array_map(fn($case) => $case->value, PermissionType::cases()))
                    ]
                )
            );
        }
    }
}
