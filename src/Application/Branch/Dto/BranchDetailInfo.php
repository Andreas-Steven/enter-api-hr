<?php

declare(strict_types=1);

namespace App\Application\Branch\Dto;

final readonly class BranchDetailInfo
{
    public function __construct(
        public ?array $branch_type,
        public ?array $attendance_policies = null,
        public ?array $work_pattern = null,
    ) {}

    public function toArray(): array
    {
        return [
            'branch_type' => $this->branch_type,
            'attendance_policies' => $this->attendance_policies,
            'work_pattern' => $this->work_pattern,
        ];
    }

    public function getBranchType(): ?array
    {
        return $this->branch_type;
    }
}