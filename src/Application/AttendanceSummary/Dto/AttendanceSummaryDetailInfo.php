<?php

declare(strict_types=1);

namespace App\Application\AttendanceSummary\Dto;

/**
 * Structured carrier for the detail_info JSONB payload of an AttendanceSummary.
 * All master data (employee, branch, work pattern) is sourced from the
 * Employee aggregate snapshot so no extra module calls are needed.
 */
final readonly class AttendanceSummaryDetailInfo
{
    public function __construct(
        public ?int $employeeId,
        public ?string $employeeName,
        public ?int $branchId,
        public ?string $branchCode,
        public ?string $branchName,
        public ?int $divisionId,
        public ?string $divisionCode,
        public ?string $divisionName,
        public ?int $positionId,
        public ?string $positionName,
        public ?int $employeeTypeId,
        public ?string $employeeTypeName,
        public ?int $workPatternId,
        public ?string $workPatternName,
    ) {}

    public static function fromArray(array $data): self
    {
        $employee = $data['employee'] ?? [];
        $branch = $data['branch'] ?? [];
        $division = $data['division'] ?? [];
        $position = $data['position'] ?? [];
        $employeeType = $data['employee_type'] ?? [];
        $workPattern = $data['work_pattern'] ?? [];

        return new self(
            employeeId: $employee['id'] ?? null,
            employeeName: $employee['name'] ?? null,
            branchId: $branch['id'] ?? null,
            branchCode: $branch['code'] ?? null,
            branchName: $branch['name'] ?? null,
            divisionId: $division['id'] ?? null,
            divisionCode: $division['code'] ?? null,
            divisionName: $division['name'] ?? null,
            positionId: $position['id'] ?? null,
            positionName: $position['name'] ?? null,
            employeeTypeId: $employeeType['id'] ?? null,
            employeeTypeName: $employeeType['name'] ?? null,
            workPatternId: $workPattern['id'] ?? null,
            workPatternName: $workPattern['name'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'employee' => [
                'id' => $this->employeeId,
                'name' => $this->employeeName,
            ],
            'branch' => [
                'id' => $this->branchId,
                'code' => $this->branchCode,
                'name' => $this->branchName,
            ],
            'division' => [
                'id' => $this->divisionId,
                'code' => $this->divisionCode,
                'name' => $this->divisionName,
            ],
            'position' => [
                'id' => $this->positionId,
                'name' => $this->positionName,
            ],
            'employee_type' => [
                'id' => $this->employeeTypeId,
                'name' => $this->employeeTypeName,
            ],
            'work_pattern' => [
                'id' => $this->workPatternId,
                'name' => $this->workPatternName,
            ],
        ];
    }
}
