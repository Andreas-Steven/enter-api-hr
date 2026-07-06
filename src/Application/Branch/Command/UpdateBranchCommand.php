<?php

declare(strict_types=1);

namespace App\Application\Branch\Command;

final readonly class UpdateBranchCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?int $branch_type,
        public ?string $code,
        public ?string $address,
        public ?int $attendance_policies_id,
        public ?int $work_pattern_id,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?string $name = null,
        ?int $branch_type = null,
        ?string $code = null,
        ?string $address = null,
        ?int $attendance_policies_id = null,
        ?int $work_pattern_id = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
        ): self {
        return new self(
            id: $id,
            name: $name,
            branch_type: $branch_type,
            code: $code,
            address: $address,
            attendance_policies_id: $attendance_policies_id,
            work_pattern_id: $work_pattern_id,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}