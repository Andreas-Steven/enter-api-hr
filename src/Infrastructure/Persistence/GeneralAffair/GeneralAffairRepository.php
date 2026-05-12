<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\GeneralAffair;

// Domain Layer
use App\Domain\GeneralAffair\Entity\GeneralAffair;
use App\Domain\GeneralAffair\Repository\GeneralAffairRepositoryInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;
use App\Domain\GeneralAffair\ValueObject\Asset;

// Infrastructure Layer
use App\Infrastructure\Concerns\HasCoreFeatures;
use App\Infrastructure\Concerns\HasMongoDBSync;
use App\Infrastructure\Concerns\ManagesPersistence;
use App\Infrastructure\Database\MongoDB\MongoDBService;
use App\Infrastructure\Persistence\GeneralAffair\MdbGeneralAffairSchema;
use App\Infrastructure\Security\CurrentUserAwareInterface;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Query\QueryConditionApplier;
use App\Shared\ValueObject\Message;

// Vendor Layer
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

// use MongoDB\Collection;

/**
 * GeneralAffair Repository using Yiisoft/Db Query Builder
 * 
 * Pure query implementation using Yiisoft/Db for database operations
 */
final class GeneralAffairRepository implements GeneralAffairRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures, HasMongoDBSync, ManagesPersistence;

    public const TABLE_NAME = 'general_affair';
    public const SEQUENCE_ID = 'general_affair_id_seq';
    private const LIKE_OPERATOR = 'ilike';

    public function __construct(
        private ConnectionInterface $db,
        private QueryConditionApplier $queryConditionApplier,
        private MongoDBService $mongoDBService,
    ) {
        $this->initMongoDBSync(
            service: $mongoDBService,
            collection: self::TABLE_NAME
        );
    }

    public function getResource(): string
    {
        return GeneralAffair::RESOURCE;
    }

    public function findById(int $id, ?int $status = null): ?GeneralAffair
    {
        /** @var array<string, mixed>|false $row */
        $row = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where([
                'id' => $id,
            ])
            ->andWhere($this->scopeWhereNotDeleted())
            ->andWhere($this->scopeByStatus($status))
            ->one();

        if (!$row) {
            return null;
        }

        return GeneralAffair::reconstitute(
            id: (int) $row['id'],
            name: (string) $row['name'],
            productId: (int) $row['product_id'],
            categoryId: (int) $row['category_id'],
            locationId: (int) $row['location_id'],
            asset: Asset::fromJson($row['asset']),
            status: ResourceStatus::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
        );
    }

    public function findByName(string $name, ?int $status = null): ?GeneralAffair
    {
        /** @var array<string, mixed>|false $row */
        $row = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['name' => $name])
            ->andWhere($this->scopeWhereNotDeleted())
            ->andWhere($this->scopeByStatus($status))
            ->one();

        if (!$row) {
            return null;
        }

        return GeneralAffair::reconstitute(
            id: (int) $row['id'],
            name: (string) $row['name'],
            productId: (int) $row['product_id'],
            categoryId: (int) $row['category_id'],
            locationId: (int) $row['location_id'],
            asset: Asset::fromJson($row['asset']),
            status: ResourceStatus::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
        )->updateSyncMdb($row[SyncMdb::field()] ?? null);
    }

    public function existsByName(string $name, ?int $status = null): bool
    {
        return (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['name' => $name])
            ->andWhere($this->scopeWhereNotDeleted())
            ->andWhere($this->scopeByStatus($status))
            ->exists();
    }

    public function list(SearchCriteria $criteria): PaginatedResult
    {
        $query = (new Query($this->db))
            ->select([
                'id',
                'name',
                'product_id',
                'category_id',
                'location_id',
                'asset',
                'status',
                'detail_info',
                SyncMdb::field(),
                LockVersion::field(),
            ])
            ->from(self::TABLE_NAME)
            ->where($this->scopeWhereNotDeleted());

        $filter = $criteria->filter;

        $this->queryConditionApplier->filterByExactMatch(
            query: $query,
            filters: $filter,
            allowedColumns: [
                'id',
                'status',
                SyncMdb::field(),
            ]
        );

        if (!empty($filter['name'])) {
            $this->queryConditionApplier->orLike(
                query: $query, 
                operator: self::LIKE_OPERATOR,
                conditions: ['name' => '%' . $filter['name'] . '%']
            );
        }

        $total = (clone $query)->count();

        $query->orderBy($criteria->getOrderClause())
            ->limit($criteria->pageSize)
            ->offset($criteria->calculateOffset());

        $rows = iterator_to_array(
            $this->streamRows(
                query: $query, 
                jsonKeys: ['asset']
            )
        );

        return new PaginatedResult(
            data: $rows,
            total: $total,
            page: $criteria->page,
            pageSize: $criteria->pageSize,
            filter: $criteria->filter,
            sort: [
                'by' => $criteria->sortBy,
                'dir' => $criteria->sortDir
            ]
        );
    }

    public function insert(GeneralAffair $entity): GeneralAffair 
    {
        return $this->db->transaction(function() use ($entity) {
            $this->db->createCommand()
                ->insert(self::TABLE_NAME, 
                    $this->mapEntityToTable(
                        entity: $entity, 
                        lockVersion: LockVersion::create()->value()
                    )
                )
                ->execute();

            $newId = (int) $this->db->getLastInsertID(self::SEQUENCE_ID);

            $newEntity = GeneralAffair::reconstitute(
                id: $newId,
                name: $entity->getName(),
                productId: $entity->getProductId(),
                categoryId: $entity->getCategoryId(),
                locationId: $entity->getLocationId(),
                asset: $entity->getAsset(),
                status: $entity->getStatus(),
                detailInfo: $entity->getDetailInfo(),
                lockVersion: LockVersion::create(),
            );

            $this->syncMongoDB(
                entity: $newEntity,
                schemaClass: MdbGeneralAffairSchema::class
            );

            return $newEntity;
        });
    }

    public function update(GeneralAffair $entity): GeneralAffair
    {
        return $this->db->transaction(function() use ($entity) {
            $currentLock = $entity->getLockVersion();
            $newLock = $this->upgradeEntityLockVersion($entity);

            $result = $this->db->createCommand()
                ->update(
                    self::TABLE_NAME, 
                    $this->mapEntityToTable(
                        entity: $entity, 
                        lockVersion: $newLock->value()
                    ), 
                    $this->buildLockCondition(
                        entity: $entity, 
                        currentLockVersion: $currentLock->value()
                    )
                )
                ->execute();
            
            if ($result === 0) {
                $this->handlePersistenceFailure($entity);
            }
            
            $this->syncMongoDB(
                entity: $entity,
                schemaClass: MdbGeneralAffairSchema::class
            );
            
            return $entity;
        });
    }

    public function delete(GeneralAffair $entity): GeneralAffair
    {
        return $this->db->transaction(function() use ($entity) {
            $result = $this->db->createCommand()
                ->update(
                    self::TABLE_NAME,
                    $this->getDeletedState(), 
                    $this->buildSimpleCondition($entity)
                )
                ->execute();

            if ($result === 0) {
                $this->handlePersistenceFailure($entity, false);
            }

            $deletedEntity = $entity->markAsDeleted();

            $this->syncMongoDB(
                entity: $deletedEntity, 
                schemaClass: MdbGeneralAffairSchema::class
            );

            return $deletedEntity;
        });
    }

    public function restore(int $id): ?GeneralAffair
    {
        // 1. Find deleted record
        /** @var array<string, mixed>|false $row */
        $row = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['id' => $id])
            ->andWhere($this->scopeWhereDeleted())
            ->one();

        if (!$row) {
            return null;
        }

        $entity = GeneralAffair::reconstitute(
            id: (int) $row['id'],
            name: (string) $row['name'],
            productId: (int) $row['product_id'],
            categoryId: (int) $row['category_id'],
            locationId: (int) $row['location_id'],
            asset: Asset::fromJson($row['asset']),
            status: ResourceStatus::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()])
        );

        $entity->restore();

        return $this->update(
            entity: $entity,
        );
    }

    private function mapEntityToTable(GeneralAffair $entity, int $lockVersion): array
    {
        return [
            'name' => $entity->getName(),
            'product_id' => $entity->getProductId(),
            'category_id' => $entity->getCategoryId(),
            'location_id' => $entity->getLocationId(),
            'asset' => $entity->getAsset()->toArray(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            SyncMdb::field() => $entity->getSyncMdbValue(),
            LockVersion::field() => $lockVersion,
        ];
    }

    public function findByUniqueCriteria(
        int $productId,
        int $categoryId,
        int $locationId,
        ?string $assetCode = null,
    ): ?GeneralAffair {
        $query = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where([
                'product_id' => $productId,
                'category_id' => $categoryId,
                'location_id' => $locationId,
            ])
            ->andWhere($this->scopeWhereNotDeleted());

        if ($assetCode !== null) {
            $query->andWhere(['like', 'asset', '%"code":"' . $assetCode . '"%', false]);
        }

        /** @var array<string, mixed>|false $row */
        $row = $query->one();

        if (!$row) {
            return null;
        }

        return GeneralAffair::reconstitute(
            id: (int) $row['id'],
            name: (string) $row['name'],
            productId: (int) $row['product_id'],
            categoryId: (int) $row['category_id'],
            locationId: (int) $row['location_id'],
            asset: Asset::fromJson($row['asset']),
            status: ResourceStatus::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
        );
    }
}
