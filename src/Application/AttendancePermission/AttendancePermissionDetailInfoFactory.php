<?php

declare(strict_types=1);

namespace App\Application\AttendancePermission;

use App\Application\AttendancePermission\Dto\AttendancePermissionDetailInfo;

use App\Domain\Shared\Enums\PermissionType;
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;
use App\Domain\Attendance\ValueObject\AttendanceRecords;
use App\Domain\Attendance\Repository\AttendanceRepositoryInterface;

use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\NotFoundException;

final readonly class AttendancePermissionDetailInfoFactory
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private AttendanceRepositoryInterface $attendanceRepository,
    ) {}

    public function buildFromPrimitives(
        ?int $employeeId = null,
        ?int $permissionId = null,
        ?string $date = null,
    ): AttendancePermissionDetailInfo {
        
        $permission = $this->buildPermissionArray($permissionId);
        $permissionType = PermissionType::from($permissionId);
        
        // 1. Build Employee Info
        $employee = null;
        if ($employeeId !== null) {
            $employeeEntity = $this->employeeRepository->findById($employeeId);
            if ($employeeEntity !== null) {
                $employee = [
                    'id' => $employeeEntity->getId(),
                    'name' => $employeeEntity->getName(),
                ];
            }
        }

        $attendanceData = null;
        if ($permissionType !== null && $permissionType->requiresRevision() && $date !== null && $employeeId !== null) {
            $attendanceEntity = $this->attendanceRepository->findByDate($date);
            
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
            employee: $employee,
            permission: $permission,
            attendance: $attendanceData,
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
                Message::create('attendance_permission.invalid_permission_id', [
                    'permission_id' => $permissionId,
                    'must_be' => implode(', ', array_map(fn($case) => $case->value, PermissionType::cases()))
                ])
            );
        }
    }
}