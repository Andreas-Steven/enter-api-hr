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
        public ?array $branch,
        public ?array $grade,
    ) {}

    public function toArray(): array
    {
        return [
            'sso' => $this->sso,
            'division' => $this->division,
            'position' => $this->position,
            'employee_type' => $this->employeeType,
            'branch' => $this->branch,
            'grade' => $this->grade,
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

    public function getBranch(): ?array
    {
        return $this->branch;
    }

    public function getGrade(): ?array
    {
        return $this->grade;
    }
}