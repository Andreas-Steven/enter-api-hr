<?php

declare(strict_types=1);

namespace App\Application\Branch\Dto;

final readonly class BranchDetailInfo
{
    public function __construct(
        public ?array $branch,
        public ?array $attendance_policies = null,
    ) {}

    public function toArray(): array
    {
        return [
            'branch' => $this->branch,
            'attendance_policies' => $this->attendance_policies,
        ];
    }

    public function getBranch(): ?array
    {
        return $this->branch;
    }
}