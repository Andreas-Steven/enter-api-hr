<?php

declare(strict_types=1);

namespace App\Domain\AttendanceDevice\Service;

// Domain Layer
use App\Domain\AttendanceDevice\Entity\AttendanceDevice;
use App\Domain\AttendanceDevice\Repository\AttendanceDeviceRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\ValueObject\Message;

/**
 * AttendanceDevice Domain Service
 * 
 * Pure business logic and domain rules
 */
final class AttendanceDeviceDomainService
{
    use DomainValidator;
    
    public function __construct(
        private AttendanceDeviceRepositoryInterface $repository
    ) {}
    
    
}
