<?php

declare(strict_types=1);

namespace App\Application\AttendancePolicies\Command;

final readonly class CreateAttendancePoliciesCommand
{
    public function __construct(
        public string $name,
        public ?array $policyDetail,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        string $name,
        ?array $policyDetail,
        int $status,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            policyDetail: $policyDetail,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}