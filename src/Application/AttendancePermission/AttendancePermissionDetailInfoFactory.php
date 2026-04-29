<?php

declare(strict_types=1);

namespace App\Application\AttendancePermission;

use App\Application\AttendancePermission\Dto\AttendancePermissionDetailInfo;

use App\Domain\Shared\Enums\PermissionType;

use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;

use App\Seeder\Faker\AttendancePermissionFaker;

final readonly class AttendancePermissionDetailInfoFactory
{
    public function __construct(
        private AttendancePermissionFaker $faker,
    ) {}

    public function buildFromPrimitives(
        ?int $employeeId = null,
        ?int $permissionId = null,
        ?int $attendance = null,
    ): AttendancePermissionDetailInfo {
        // 1. Build Permission Array
        $permission = $this->buildPermissionArray($permissionId);
        $permissionType = PermissionType::from($permissionId);
        
        // 2. Build Employee
        $employee = $this->faker->employeeFaker($employeeId);

        // 3. Logic Attendance: Hanya isi jika permissionType membutuhkan revisi (LATE/LEAVE_EARLY)
        $attendanceData = null;
        if ($permissionType !== null && $permissionType->requiresRevision()) {
            $attendanceData = $this->faker->attendanceFaker();
        }

        return new AttendancePermissionDetailInfo(
            employee: $employee,
            permission: $permission,
            attendance: $attendanceData,
        );
    }

    private function buildPermissionArray(?int $permissionId): ?array
    {
        if ($permissionId === null) {
            return null;
        }

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
                    ],
                    domain: 'app',
                )
            );
        }
    }
}