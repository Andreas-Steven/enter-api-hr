<?php

declare(strict_types=1);

namespace App\Application\Employee\Command;

final readonly class CreateEmployeeCommand
{
    public function __construct(
        public string $name,
        public string $username,
        public int $ssoId,
        public int $divisionId,
        public int $positionId,
        public int $employeeTypeId,
        public int $branchId,
        public ?int $gradeId,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        string $name,
        string $username,
        int $ssoId,
        int $divisionId,
        int $positionId,
        int $employeeTypeId,
        int $branchId,
        ?int $gradeId,
        int $status,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            username: $username,
            ssoId: $ssoId,
            divisionId: $divisionId,
            positionId: $positionId,
            employeeTypeId: $employeeTypeId,
            branchId: $branchId,
            gradeId: $gradeId,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}