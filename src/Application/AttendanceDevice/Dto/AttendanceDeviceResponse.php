<?php

declare(strict_types=1);

namespace App\Application\AttendanceDevice\Dto;

use App\Domain\AttendanceDevice\Entity\AttendanceDevice;

final readonly class AttendanceDeviceResponse
{
    /**
     * Kita definisikan properti yang HANYA boleh dilihat oleh klien/API.
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $code,
        public string $address,
        public string $ip_address,
        public array $location,
        public int $requires_location,
        public int $status,
        public ?int $sync_mdb,
        public array $detail_info,
        public int $lock_version,
    ) {}

    /**
     * Static Factory Method: Mengubah Entity menjadi DTO.
     * Di sini kita melakukan transformasi format data.
     */
    public static function fromEntity(AttendanceDevice $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            code: $entity->getCode(),
            address: $entity->getAddress(),
            ip_address: $entity->getIpAddress()->value(),
            location: $entity->getLocation()->toArray(),
            requires_location: $entity->getRequiresLocation(),
            status: $entity->getStatus()->value(),
            detail_info: $entity->getDetailInfo()->toArray(),
            sync_mdb: $entity->getSyncMdbValue(),
            lock_version: $entity->getLockVersion()->value(),
        );
    }

    /**
     * Helper untuk mengubah objek menjadi array agar bisa di-encode ke JSON.
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}