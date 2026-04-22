<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\AttendanceDevice;

// Domain Layer
use App\Domain\AttendanceDevice\Entity\AttendanceDevice;
use App\Domain\AttendanceDevice\Repository\AttendanceDeviceRepositoryInterface;

use App\Domain\AttendanceDevice\ValueObject\IpAddress;
use App\Domain\AttendanceDevice\ValueObject\DeviceLocation;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;

// Infrastructure Layer
use App\Infrastructure\Concerns\HasCoreFeatures;
use App\Infrastructure\Concerns\HasMongoDBSync;
use App\Infrastructure\Concerns\ManagesPersistence;
use App\Infrastructure\Database\MongoDB\MongoDBService;
use App\Infrastructure\Persistence\AttendanceDevice\MdbAttendanceDeviceSchema;
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
 * AttendanceDevice Repository using Yiisoft/Db Query Builder
 * 
 * Pure query implementation using Yiisoft/Db for database operations
 */
final class AttendanceDeviceRepository implements AttendanceDeviceRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures, HasMongoDBSync, ManagesPersistence;

    public const TABLE_NAME = 'attendance_device';
    public const SEQUENCE_ID = 'attendance_device_id_seq';
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
        return AttendanceDevice::RESOURCE;
    }

    public function findById(int $id, ?int $status = null): ?AttendanceDevice
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

        return AttendanceDevice::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            code: $row['code'],
            address: $row['address'],
            requiresLocation: (int)$row['requires_location'],
            ipAddress: IpAddress::create($row['ip_address']),
            location: DeviceLocation::fromString($row['location']),
            status: ResourceStatus::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
        );
    }

    public function findByName(string $name, ?int $status = null): ?AttendanceDevice
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

        return AttendanceDevice::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            status: ResourceStatus::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
        )->updateSyncMdb($row[SyncMdb::field()] ?? null);
    }

    /**
     * @param string[] $codes
     * @return array<string, AttendanceDevice>
     */
    public function findAllByCodes(array $codes): array
    {
        if (empty($codes)) {
            return [];
        }

        /** @var array<int, array<string, mixed>> $rows */
        $rows = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['code' => $codes]) // Yii3 otomatis mengubah array menjadi IN ('code1', 'code2')
            ->andWhere($this->scopeWhereNotDeleted())
            ->all();

        $devices = [];
        foreach ($rows as $row) {
            $device = AttendanceDevice::reconstitute(
                id: (int) $row['id'],
                name: $row['name'],
                code: $row['code'],
                address: $row['address'],
                requiresLocation: (int)$row['requires_location'],
                ipAddress: IpAddress::create($row['ip_address']),
                location: DeviceLocation::fromString($row['location']),
                status: ResourceStatus::from((int)$row['status']),
                detailInfo: DetailInfo::fromJson($row['detail_info']),
                lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
            );

            // Index berdasarkan code untuk mempermudah lookup di Domain Service
            $devices[$device->getCode()] = $device;
        }

        return $devices;
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
                'code',
                'address',
                'requires_location',
                'ip_address',
                'location',
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
                'code',
                'address',
                'requires_location',
                'ip_address',
                'location',
                SyncMdb::field(),
            ]
        );

        if (!empty($filter['name'])) {
            $this->queryConditionApplier->orLike(
                query: $query, 
                operator: self::LIKE_OPERATOR,
                conditions: ['name' => $filter['name']]
            );
        }

        $total = (clone $query)->count();

        $query->orderBy($criteria->getOrderClause())
            ->limit($criteria->pageSize)
            ->offset($criteria->calculateOffset());

        $rows = iterator_to_array(
            $this->streamRows(
                query: $query, 
                jsonKeys: [
                    'location',
                ]
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

    public function insert(AttendanceDevice $entity): AttendanceDevice 
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

            $newEntity = AttendanceDevice::reconstitute(
                id: $newId,
                name: $entity->getName(),
                code: $entity->getCode(),
                address: $entity->getAddress(),
                requiresLocation: $entity->getRequiresLocation(),
                ipAddress: $entity->getIpAddress(),
                location: $entity->getLocation(),
                status: $entity->getStatus(),
                detailInfo: $entity->getDetailInfo(),
                lockVersion: LockVersion::create(),
            );

            $this->syncMongoDB(
                entity: $newEntity,
                schemaClass: MdbAttendanceDeviceSchema::class
            );

            return $newEntity;
        });
    }

    public function update(AttendanceDevice $entity): AttendanceDevice
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
                schemaClass: MdbAttendanceDeviceSchema::class
            );
            
            return $entity;
        });
    }

    public function delete(AttendanceDevice $entity): AttendanceDevice
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
                schemaClass: MdbAttendanceDeviceSchema::class
            );

            return $deletedEntity;
        });
    }

    public function restore(int $id): ?AttendanceDevice
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

        $entity = AttendanceDevice::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            code: $row['code'],
            address: $row['address'],
            requiresLocation: (int)$row['requires_location'],
            ipAddress: IpAddress::create($row['ip_address']),
            location: DeviceLocation::fromString($row['location']),
            status: ResourceStatus::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()])
        );

        $entity->restore();

        return $this->update(
            entity: $entity,
        );
    }
    

    private function mapEntityToTable(AttendanceDevice $entity, int $lockVersion): array
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
            SyncMdb::field() => $entity->getSyncMdbValue(),
            LockVersion::field() => $lockVersion,
        ];
    }
}
