<?php

declare(strict_types=1);

namespace App\Application\AttendanceDevice;

// Application Layer
use App\Application\AttendanceDevice\Command\CreateAttendanceDeviceCommand;
use App\Application\AttendanceDevice\Command\UpdateAttendanceDeviceCommand;
use App\Application\AttendanceDevice\Dto\AttendanceDeviceResponse;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\AttendanceDevice\Entity\AttendanceDevice;
use App\Domain\AttendanceDevice\Repository\AttendanceDeviceRepositoryInterface;
use App\Domain\AttendanceDevice\Service\AttendanceDeviceDomainService;
use App\Domain\AttendanceDevice\ValueObject\IpAddress;
use App\Domain\AttendanceDevice\ValueObject\DeviceLocation;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\Security\AuthorizerInterface;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * AttendanceDevice Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class AttendanceDeviceApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private AttendanceDeviceRepositoryInterface $repository,
        private AttendanceDeviceDomainService $domainService
    ) {
    }

    public function getResource(): string
    {
        return AttendanceDevice::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): AttendanceDevice
    {
        $data = $this->repository->findById(
            id: $id,
            status: $status
        );

        if ($data === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found', 
                    params: [
                        'resource' => $this->getResource(),
                        'field' => 'id',
                        'value' => $id
                    ]
                )
            );
        }
        
        return $data;
    }
    
    public function list(SearchCriteria $criteria): PaginatedResult
    {
        return $this->repository->list(
            criteria: $criteria
        );
    }

    public function view(int $id): AttendanceDeviceResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );
        
        return AttendanceDeviceResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreateAttendanceDeviceCommand $command): AttendanceDeviceResponse
    {
        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: []
            )
            ->build();

        $data = AttendanceDevice::create(
            name: $command->name,
            code: $command->code,
            address: $command->address,
            ipAddress: IpAddress::create($command->ipAddress),
            requiresLocation: (int) $command->requiresLocation,
            location: DeviceLocation::fromArray($command->location),
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        return AttendanceDeviceResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
    }

    public function update(int $id, UpdateAttendanceDeviceCommand $command): AttendanceDeviceResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );

        $this->repository->verifyLockVersion(
            entity: $data,
            version: $command->lockVersion ?? null
        );

        $newStatus = ResourceStatus::tryFrom($command->status);

        $hasFieldChanges = $data->hasFieldChanges(
            data: (array) $command,
            removeNulls: true
        );

        $data->guardAgainstInvalidTransition(
            hasFieldChanges: $hasFieldChanges,
            newStatus: $newStatus
        );

        $data->updateName(
            newName: $command->name
        );
        
        $data->applyStatus(
            newStatus: $newStatus
        );

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: $command->detailInfo ?? [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return AttendanceDeviceResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): AttendanceDeviceResponse
    {
        $data = $this->getEntityById($id);

        $this->repository->verifyLockVersion($data, $lockVersion);

        $this->domainService->guardPermission(
            authorizer: $this->auth,
            permission: 'attendance-device.delete',
            resource: $this->getResource(),
            id: $id
        );
        
        $this->domainService->validateCanBeDeleted(
            entity: $data,
            resource: $this->getResource(),
        );

        $detailInfo = $this->detailInfoFactory
            ->delete(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->build();

        $data->updateDetailInfo(detailInfo: $detailInfo);

        return AttendanceDeviceResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): AttendanceDeviceResponse
    {
        $data = $this->repository->restore($id);
        
        if ($data === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found', 
                    params: [
                        'resource' => $this->getResource(),
                        'field' => 'id',
                        'value' => $id
                    ]
                )
            );
        }

        $detailInfo = $this->detailInfoFactory
            ->restore(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->build();

        $data->updateDetailInfo(detailInfo: $detailInfo);

        $restoredData = $this->repository->restore(
            id: $data->getId()
        );
        
        return AttendanceDeviceResponse::fromEntity(
            entity: $restoredData
        );
    }
}
