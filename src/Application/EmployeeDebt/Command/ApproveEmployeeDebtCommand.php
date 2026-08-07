<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebt\Command;

final readonly class ApproveEmployeeDebtCommand
{
    public function __construct(
        public int $id,
        public ?int $approved,
        public ?string $approvedNote,
        public ?int $status,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?int $approved = null,
        ?string $approvedNote = null,
        ?int $status = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            approved: $approved,
            approvedNote: $approvedNote,
            status: $status,
            lockVersion: $lockVersion,
        );
    }
}
