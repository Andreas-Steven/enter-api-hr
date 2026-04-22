<?php

declare(strict_types=1);

namespace App\Application\AttendanceDevice\Command;

final readonly class UpdateAttendanceDeviceCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $code,
        public ?string $address,
        public ?string $ipAddress,
        public ?array $location,
        public ?int $requiresLocation,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?string $name = null,
        ?string $code = null,
        ?string $address = null,
        ?string $ipAddress = null,
        ?array $location = null,
        ?int $requiresLocation = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            code: $code,
            address: $address,
            ipAddress: $ipAddress,
            location: $location,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}