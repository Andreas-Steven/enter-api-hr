<?php

declare(strict_types=1);

namespace App\Application\Branch\Command;

final readonly class CreateBranchCommand
{
    public function __construct(
        public string $name,
        public int $branch_type,
        public string $code,
        public string $address,
        public ?int $attendance_policies_id,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        string $name,
        int $branch_type,
        string $code,
        string $address,
        ?int $attendance_policies_id,
        int $status,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            branch_type: $branch_type,
            code: $code,
            address: $address,
            attendance_policies_id: $attendance_policies_id,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}