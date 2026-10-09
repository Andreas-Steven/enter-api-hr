<?php

declare(strict_types=1);

namespace App\Application\Employee\Command;

final readonly class UpdateEmployeeCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $username,
        public ?int $divisionId,
        public ?int $positionId,
        public ?int $employeeTypeId,
        public ?int $branchId,
        public ?int $gradeId,
        public ?string $nik,
        public ?string $email,
        public ?string $birthDate,
        public ?string $dateStart,
        public ?string $dateResign,
        public ?array $contact,
        public ?array $image,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?string $name,
        ?string $username,
        ?int $divisionId,
        ?int $positionId,
        ?int $employeeTypeId,
        ?int $branchId,
        ?int $gradeId,
        ?string $nik,
        ?string $email,
        ?string $birthDate,
        ?string $dateStart,
        ?string $dateResign,
        ?array $contact,
        ?array $image,
        ?int $status,
        ?array $detailInfo,
        ?int $lockVersion,
    ): self {
        return new self(
            id: $id,
            name: $name,
            username: $username,
            divisionId: $divisionId,
            positionId: $positionId,
            employeeTypeId: $employeeTypeId,
            branchId: $branchId,
            gradeId: $gradeId,
            nik: $nik,
            email: $email,
            birthDate: $birthDate,
            dateStart: $dateStart,
            dateResign: $dateResign,
            contact: $contact,
            image: $image,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}