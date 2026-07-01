<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Employee;

// Domain Layer
use App\Domain\Employee\Entity\Employee;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbEmployeeSchema
{
    public static function toArray(Employee $entity): array
    {
        return [
            'name' => $entity->getName(),
            'nik' => $entity->getNik(),
            'email' => $entity->getEmail(),
            'contact' => $entity->getContact()->toArray(),
            'birth_date' => $entity->getBirthDate(),
            'date_start' => $entity->getDateStart(),
            'date_resign' => $entity->getDateResign(),
            'sso_id' => $entity->getSsoId(),
            'division_id' => $entity->getDivisionId(),
            'position_id' => $entity->getPositionId(),
            'employee_type_id' => $entity->getEmployeeTypeId(),
            'image' => $entity->getImage()->toArray(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}