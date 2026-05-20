<?php

declare(strict_types=1);

namespace App\Application\Branch\Dto;

final readonly class BranchDetailInfo
{
    public function __construct(
        public ?array $branch,
    ) {}

    public function toArray(): array
    {
        return [
            'branch' => $this->branch,
        ];
    }

    public function getBranch(): ?array
    {
        return $this->branch;
    }
}