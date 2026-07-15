<?php

declare(strict_types=1);

namespace App\Application\AttendancePolicies;

// Application Layer
use App\Application\AttendancePolicies\Command\CreateAttendancePoliciesCommand;
use App\Application\AttendancePolicies\Command\UpdateAttendancePoliciesCommand;
use App\Application\AttendancePolicies\Dto\AttendancePoliciesResponse;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\AttendancePolicies\Entity\AttendancePolicies;
use App\Domain\AttendancePolicies\Repository\AttendancePoliciesRepositoryInterface;
use App\Domain\AttendancePolicies\Service\AttendancePoliciesDomainService;
use App\Domain\AttendancePolicies\ValueObject\PolicyDetail;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * AttendancePolicies Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class AttendancePoliciesApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private AttendancePoliciesRepositoryInterface $repository,
        private AttendancePoliciesDomainService $domainService
    ) {
    }

    public function getResource(): string
    {
        return AttendancePolicies::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): AttendancePolicies
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

    public function view(int $id): AttendancePoliciesResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );
        
        return AttendancePoliciesResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreateAttendancePoliciesCommand $command): AttendancePoliciesResponse
    {
        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: []
            )
            ->build();

        $data = AttendancePolicies::create(
            name: $command->name,
            policyDetail: PolicyDetail::fromArray($command->policyDetail),
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        return AttendancePoliciesResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
    }

    public function update(int $id, UpdateAttendancePoliciesCommand $command): AttendancePoliciesResponse
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

        if (!empty($command->policyDetail)) {
            $data->updatePolicyDetail(
                policyDetail: PolicyDetail::fromArray($command->policyDetail)
            );
        }

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: $command->detailInfo ?? [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return AttendancePoliciesResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): AttendancePoliciesResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );

        $this->repository->verifyLockVersion(
            entity: $data, 
            version: $lockVersion,
        );

        $this->domainService->guardPermission(
            id: $id,
            authorizer: $this->auth,
            permission: 'attendance-policies.delete',
            resource: $this->getResource(),
        );
        
        $this->domainService->ensureDeletable(
            entity: $data,
            resource: $this->getResource(),
        );

        $detailInfo = $this->detailInfoFactory
            ->delete(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return AttendancePoliciesResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): AttendancePoliciesResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: ResourceStatus::deleted()->value()
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

        $data->guardAgainstInvalidTransition(
            hasFieldChanges: false,
            newStatus: ResourceStatus::restored()
        );

        $data->markAsRestored();

        $detailInfo = $this->detailInfoFactory
            ->restore(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        $restoredData = $this->repository->restore(
            id: $data->getId()
        );
        
        return AttendancePoliciesResponse::fromEntity(
            entity: $restoredData
        );
    }
}