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
        public int $workTimeId,
        public int $employeeTypeId,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        string $name,
        string $username,
        int $ssoId,
        int $divisionId,
        int $positionId,
        int $workTimeId,
        int $employeeTypeId,
        int $status,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            username: $username,
            ssoId: $ssoId,
            divisionId: $divisionId,
            positionId: $positionId,
            workTimeId: $workTimeId,
            employeeTypeId: $employeeTypeId,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}