<?php

declare(strict_types=1);

namespace App\Domain\AttendanceDevice\Entity;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;

// Domain Layer
use App\Domain\Shared\Concerns\Entity\Stateful;
use App\Domain\Shared\Concerns\Entity\Identifiable;
use App\Domain\Shared\Concerns\Entity\Descriptive;

use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;

use App\Domain\AttendanceDevice\ValueObject\IpAddress;
use App\Domain\AttendanceDevice\ValueObject\DeviceLocation;

final class AttendanceDevice
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'Attendance Device';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private string $code,
        private string $address,
        private int $requiresLocation,
        private IpAddress $ipAddress,
        private DeviceLocation $location,
        private ResourceStatus $status,
        private DetailInfo $detailInfo,
        private ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ) {
        $this->resource = self::RESOURCE;
        $this->lockVersion = $lockVersion ?? LockVersion::create();
    }

    public static function getResource(): string
    {
        return self::RESOURCE;
    }

    public static function create(
        string $name,
        string $code,
        string $address,
        int $requiresLocation,
        IpAddress $ipAddress,
        DeviceLocation $location,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
    ): self {
        self::guardInitialStatus($status, self::RESOURCE);

        return new self(
            id: null, 
            name: $name, 
            code: $code, 
            address: $address, 
            ipAddress: $ipAddress, 
            requiresLocation: $requiresLocation,
            location: $location, 
            status: $status, 
            detailInfo: $detailInfo, 
            syncMdb: $syncMdb, 
            lockVersion: LockVersion::create()
        );
    }

    public static function reconstitute(
        int $id,
        string $name,
        string $code,
        string $address,
        int $requiresLocation,
        IpAddress $ipAddress,
        DeviceLocation $location,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ): self {

        return new self(
            id: $id, 
            name: $name, 
            code: $code, 
            address: $address, 
            ipAddress: $ipAddress, 
            requiresLocation: $requiresLocation,
            location: $location, 
            status: $status, 
            detailInfo: $detailInfo, 
            syncMdb: $syncMdb, 
            lockVersion: $lockVersion ?? LockVersion::create()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'requires_location' => $this->requiresLocation,
            'ip_address' => $this->ipAddress->value(),
            'location' => $this->location->toArray(),
            'status' => $this->status->value(),
            'detail_info' => $this->detailInfo->toArray(),
            SyncMdb::field() => $this->syncMdb?->value(),
            LockVersion::field() => $this->lockVersion->value(),
        ];
    }

    public function toPersistance(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'requires_location' => $this->requiresLocation,
            'ip_address' => $this->ipAddress->value(),
            'location' => $this->location->toArray(),
        ];
    }

    public function restore(): void
    {
        if (!$this->status->isDeleted()) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'resource.not_deleted',
                    params: ['id' => $this->id]
                )
            );
        }
        
        $this->status = ResourceStatus::restored();
    }

    /**
     * Place AttendanceDevice-specific business functions here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     */

    public function getCode(): string
    {
        return $this->code;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getIpAddress(): IpAddress
    {
        return $this->ipAddress;
    }
    
    public function getLocation(): DeviceLocation
    {
        return $this->location;
    }

    public function getRequiresLocation(): int
    {
        return $this->requiresLocation;
    }

    public static function toUnregisteredSnapshot(): array
    {
        return [
            'id'   => 0,
            'code' => 'URG',
            'name' => 'Unregistered Device',
        ];
    }

    public static function toManualSnapshot(): array
    {
        return [
            'id'   => 0,
            'code' => 'OVR',
            'name' => 'Override',
        ];
    }

    public function toSnapshot(): array
    {
        return [
            'id'   => $this->id,
            'code' => $this->code,
            'name' => $this->name,
        ];
    }
}
