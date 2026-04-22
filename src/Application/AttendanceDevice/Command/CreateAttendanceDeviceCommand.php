<?php

declare(strict_types=1);

namespace App\Application\AttendanceDevice\Command;

final readonly class CreateAttendanceDeviceCommand
{
    public function __construct(
        public string $name,
        public string $code,
        public string $address,
        public string $ipAddress,
        public array $location,
        public int $requiresLocation,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        string $name,
        string $code,
        string $address,
        string $ipAddress,
        array $location,
        int $requiresLocation,
        int $status,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            code: $code,
            address: $address,
            ipAddress: $ipAddress,
            location: $location,
            requiresLocation: $requiresLocation,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}