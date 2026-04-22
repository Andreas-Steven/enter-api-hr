<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\AttendanceDevice;

// Domain Layer
use App\Domain\AttendanceDevice\Entity\AttendanceDevice;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbAttendanceDeviceSchema
{
    public static function toArray(AttendanceDevice $entity): array
    {
        return [
            'name' => $entity->getName(),
            'code' => $entity->getCode(),
            'address' => $entity->getAddress(),
            'requires_location' => $entity->getRequiresLocation(),
            'ip_address' => $entity->getIpAddress()->value(),
            'location' => $entity->getLocation()->toArray(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}