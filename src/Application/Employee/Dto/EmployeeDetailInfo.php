<?php

declare(strict_types=1);

namespace App\Application\Employee\Dto;

final readonly class EmployeeDetailInfo
{
    public function __construct(
        public ?array $sso,
        public ?array $division,
        public ?array $position,
        public ?array $employeeType,
        public ?array $workTime,
    ) {}

    public function toArray(): array
    {
        return [
            'sso' => $this->sso,
            'division' => $this->division,
            'position' => $this->position,
            'employee_type' => $this->employeeType,
            'work_time' => $this->workTime,
        ];
    }

    public function getSso(): ?array
    {
        return $this->sso;
    }

    public function getDivision(): ?array
    {
        return $this->division;
    }

    public function getPosition(): ?array
    {
        return $this->position;
    }

    public function getEmployeeType(): ?array
    {
        return $this->employeeType;
    }

    public function getWorkTime(): ?array
    {
        return $this->workTime;
    }
}