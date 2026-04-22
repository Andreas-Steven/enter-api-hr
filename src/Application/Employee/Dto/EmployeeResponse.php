<?php

declare(strict_types=1);

namespace App\Application\Employee\Dto;

use App\Domain\Employee\Entity\Employee;

final readonly class EmployeeResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $username,
        public int $sso_id,
        public ?string $nik,
        public ?string $email,
        public ?array $contact,
        public ?string $birth_date,
        public ?string $date_start,
        public ?string $date_resign,
        public ?int $division_id,
        public ?int $position_id,
        public ?int $employee_type_id,
        public ?int $work_time_id,
        public ?array $image,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(Employee $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            username: $entity->getUsername(),
            sso_id: $entity->getSsoId(),
            nik: $entity->getNik(),
            email: $entity->getEmail(),
            contact: $entity->getContact()?->toArray(),
            birth_date: $entity->getBirthDate(),
            date_start: $entity->getDateStart(),
            date_resign: $entity->getDateResign(),
            division_id: $entity->getDivisionId(),
            position_id: $entity->getPositionId(),
            employee_type_id: $entity->getEmployeeTypeId(),
            work_time_id: $entity->getWorkTimeId(),
            image: $entity->getImage()?->toArray(),
            status: $entity->getStatus()->value(),
            detail_info: $entity->getDetailInfo()->toArray(),
            sync_mdb: $entity->getSyncMdbValue(),
            lock_version: $entity->getLockVersion()->value(),
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}