<?php

declare(strict_types=1);

namespace App\Application\GeneralAffair;

// Application Layer
use App\Application\GeneralAffair\Command\CreateGeneralAffairCommand;
use App\Application\GeneralAffair\Command\UpdateGeneralAffairCommand;
use App\Application\GeneralAffair\Dto\GeneralAffairResponse;
use App\Application\Shared\Factory\DetailInfoFactory;
use App\Application\GeneralAffair\GeneralAffairDetailInfoFactory;

// Domain Layer
use App\Domain\GeneralAffair\Entity\GeneralAffair;
use App\Domain\GeneralAffair\Repository\GeneralAffairRepositoryInterface;
use App\Domain\GeneralAffair\Service\GeneralAffairDomainService;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Domain User and Time
use App\Domain\Shared\Contract\DateTimeProviderInterface;
use App\Domain\Shared\Contract\CurrentUserInterface;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * GeneralAffair Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class GeneralAffairApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private GeneralAffairRepositoryInterface $repository,
        private GeneralAffairDomainService $domainService,
        private GeneralAffairDetailInfoFactory $generalDetailInfoFactory,
        private DateTimeProviderInterface $dateTimeProvider,
        private CurrentUserInterface $currentUser,
    ) {
    }

    public function getResource(): string
    {
        return GeneralAffair::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): GeneralAffair
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

    public function view(int $id): GeneralAffairResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );
        
        return GeneralAffairResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreateGeneralAffairCommand $command): GeneralAffairResponse
    {
        $now = $this->dateTimeProvider->iso8601();
        $username = $this->currentUser->getActor()?->getUsername();

        $existingEntity = $this->repository->findByUniqueCriteria(
            productId: $command->productId,
            categoryId: $command->categoryId,
            locationId: $command->locationId
        );

        if ($existingEntity) {
            $updatedAsset = $this->domainService->buildAssetFromPayload(
                assetInput: $command->asset,
                oldData: $existingEntity->getAsset()->toArray(),
                username: $username,
                now: $now,
            );

            $existingEntity->updateAsset(
                asset: $updatedAsset
            );

            $generalDetailInfo = $this->generalDetailInfoFactory->buildFromPrimitives(
                productId: $command->productId,
                categoryId: $command->categoryId,
                locationId: $command->locationId,
            );

            $detailInfo = $this->detailInfoFactory
                ->update(
                    detailInfo: $existingEntity->getDetailInfo(),
                    payload: array_merge(
                        $generalDetailInfo->toArray(),
                        $command->detailInfo ?? [],
                    ),
                )
                ->build();
                
            $existingEntity->updateDetailInfo(
                detailInfo: $detailInfo
            );

            return GeneralAffairResponse::fromEntity(
                entity: $this->repository->update(
                    entity: $existingEntity
                )
            );
        } else {
            $generalDetailInfo = $this->generalDetailInfoFactory->buildFromPrimitives(
                productId: $command->productId,
                categoryId: $command->categoryId,
                locationId: $command->locationId,
            );

            $detailInfo = $this->detailInfoFactory
                ->create(
                    detailInfo: $generalDetailInfo->toArray(),
                )
                ->build();
    
            $data = GeneralAffair::create(
                /* name: $command->name, */
                productId: $command->productId,
                categoryId: $command->categoryId,
                locationId: $command->locationId,
                asset: $this->domainService->buildAssetFromPayload(
                    assetInput: $command->asset,
                    oldData: [],
                    username: $username,
                    now: $now,
                ),
                status: ResourceStatus::from($command->status),
                detailInfo: $detailInfo
            );
    
            return GeneralAffairResponse::fromEntity(
                entity: $this->repository->insert(
                    entity: $data
                )
            );
        }
    }

    public function update(int $id, UpdateGeneralAffairCommand $command): GeneralAffairResponse
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

        /* if ($command->name !== null) {
            $data->updateName(
                newName: $command->name
            );
        } */

        if ($command->productId !== null) {
            $data->updateProductId(
                productId: $command->productId
            );
        }

        if ($command->categoryId !== null) {
            $data->updateCategoryId(
                categoryId: $command->categoryId
            );
        }

        if ($command->locationId !== null) {
            $data->updateLocationId(
                locationId: $command->locationId
            );
        }

        $data->applyStatus(
            newStatus: $newStatus
        );

        $generalDetailInfo = $this->generalDetailInfoFactory->buildFromPrimitives(
            productId: $data->getProductId(),
            categoryId: $data->getCategoryId(),
            locationId: $data->getLocationId(),
        );

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: $generalDetailInfo->toArray()
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return GeneralAffairResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null, ?string $code = null): GeneralAffairResponse
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
            permission: 'general-affair.delete',
            resource: $this->getResource(),
        );

        if ($code !== null) {
            $now = $this->dateTimeProvider->iso8601();
            $username = $this->currentUser->getActor()?->getUsername() ?? 'system';

            $updatedAsset = $this->domainService->buildAssetWithDeletedItemCode(
                oldData: $data->getAsset()->toArray(),
                code: $code,
                username: $username,
                now: $now,
            );

            $data->updateAsset(
                asset: $updatedAsset
            );

            $detailInfo = $this->detailInfoFactory
                ->update(
                    detailInfo: $data->getDetailInfo(),
                    payload: [],
                )
                ->build();

            $data->updateDetailInfo(
                detailInfo: $detailInfo
            );

            return GeneralAffairResponse::fromEntity(
                entity: $this->repository->update(
                    entity: $data
                )
            );
        }

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

        return GeneralAffairResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): GeneralAffairResponse
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
        
        return GeneralAffairResponse::fromEntity(
            entity: $restoredData
        );
    }
}