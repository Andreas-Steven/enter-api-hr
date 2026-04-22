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
        public ?int $workTimeId,
        public ?int $employeeTypeId,
        public ?string $nik,
        public ?string $email,
        public ?string $birthDate,
        public ?string $dateStart,
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
        ?int $workTimeId,
        ?int $employeeTypeId,
        ?string $nik,
        ?string $email,
        ?string $birthDate,
        ?string $dateStart,
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
            workTimeId: $workTimeId,
            employeeTypeId: $employeeTypeId,
            nik: $nik,
            email: $email,
            birthDate: $birthDate,
            dateStart: $dateStart,
            contact: $contact,
            image: $image,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}