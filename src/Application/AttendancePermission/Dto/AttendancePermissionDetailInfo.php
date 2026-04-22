<?php

declare(strict_types=1);

namespace App\Application\AttendancePermission\Dto;

final readonly class AttendancePermissionDetailInfo
{
    public function __construct(
        public ?array $employee,
        public ?array $attendance,
        public ?array $permission,
    ) {}

    public function toArray(): array
    {
        return [
            'employee' => $this->employee,
            'attendance' => $this->attendance,
            'permission' => $this->permission,
        ];
    }

    public function getEmployee(): ?array
    {
        return $this->employee;
    }

    public function getAttendance(): ?array
    {
        return $this->attendance;
    }

    public function getPermission(): ?array
    {
        return $this->permission;
    }
}