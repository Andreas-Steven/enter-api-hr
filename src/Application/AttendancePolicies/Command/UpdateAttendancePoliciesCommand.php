<?php

declare(strict_types=1);

namespace App\Application\AttendancePolicies\Command;

final readonly class UpdateAttendancePoliciesCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?array $policyDetail,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?string $name = null,
        ?array $policyDetail = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            policyDetail: $policyDetail,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}