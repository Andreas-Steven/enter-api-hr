<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebt\Command;

final readonly class RejectEmployeeDebtCommand
{
    public function __construct(
        public int $id,
        public ?string $approvedNote,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?string $approvedNote = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            approvedNote: $approvedNote,
            lockVersion: $lockVersion,
        );
    }
}
