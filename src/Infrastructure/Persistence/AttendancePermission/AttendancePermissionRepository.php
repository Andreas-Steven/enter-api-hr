<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\AttendancePermission;

// Domain Layer
use App\Domain\AttendancePermission\Repository\AttendancePermissionRepositoryInterface;
use App\Domain\AttendancePermission\Entity\AttendancePermission;
use App\Domain\AttendancePermission\ValueObject\Revision;
use App\Domain\AttendancePermission\ValueObject\DateRange;
use App\Domain\Attendance\ValueObject\AttendancePeriod;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;

// Infrastructure Layer
use App\Infrastructure\Concerns\HasCoreFeatures;
use App\Infrastructure\Concerns\HasMongoDBSync;
use App\Infrastructure\Concerns\ManagesPersistence;
use App\Infrastructure\Database\MongoDB\MongoDBService;
use App\Infrastructure\Persistence\AttendancePermission\MdbAttendancePermissionSchema;
use App\Infrastructure\Security\CurrentUserAwareInterface;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Query\QueryConditionApplier;
use App\Shared\ValueObject\Message;

// Vendor Layer
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * AttendancePermission Repository using Yiisoft/Db Query Builder
 * 
 * Pure query implementation using Yiisoft/Db for database operations
 */
final class AttendancePermissionRepository implements AttendancePermissionRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures, HasMongoDBSync, ManagesPersistence;

    public const TABLE_NAME = 'attendance_permission';
    public const SEQUENCE_ID = 'attendance_permission_id_seq';
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
        return AttendancePermission::RESOURCE;
    }

    public function findById(int $id, ?int $status = null): ?AttendancePermission
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

        return AttendancePermission::reconstitute(
            id: (int) $row['id'],
            // name: $row['name'],
            employeeId: (int) $row['employee_id'],
            permissionType: (int) $row['permission_type'],
            date: $row['date'],
            note: $row['note'],
            approved: $row['approved'] !== null ? (int) $row['approved'] : null,
            approvedNote: $row['approved_note'],
            revision: Revision::fromJson($row['revision']),
            status: ResourceStatus::from((int) $row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
        );
    }

    public function findByName(string $name, ?int $status = null): ?AttendancePermission
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

        return AttendancePermission::reconstitute(
            id: (int) $row['id'],
            // name: $row['name'],
            employeeId: $row['employee_id'],
            permissionType: $row['permission_type'],
            date: $row['date'],
            note: $row['note'],
            approved: $row['approved'],
            approvedNote: $row['approved_note'],
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
                // 'name',
                'employee_id',
                'permission_type',
                'date',
                'note',
                'approved',
                'approved_note',
                'revision',
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
                'employee_id',
                'permission_type',
                'note',
                'approved_note',
                'revision',
                'status', 
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
        
        if (!empty($filter['note'])) {
            $this->queryConditionApplier->orLike(
                query: $query, 
                operator: self::LIKE_OPERATOR,
                conditions: ['note' => '%' . $filter['note'] . '%']
            );
        }

        if (!empty($filter['approved_note'])) {
            $this->queryConditionApplier->orLike(
                query: $query, 
                operator: self::LIKE_OPERATOR,
                conditions: ['approved_note' => '%' . $filter['approved_note'] . '%']
            );
        }

        if (array_key_exists('approved', $filter)) {
            if ($filter['approved'] === null || $filter['approved'] === 'null') {
                $query->andWhere(['approved' => null]);
            } else {
                $query->andWhere(['approved' => $filter['approved']]);
            }
        }

        // Date range fields
        if (!empty($filter['date'])) {
            $range = DateRange::fromFilterString($filter['date']);
            $query->andWhere(['>=', 'date', $range->startDate])
                  ->andWhere(['<=', 'date', $range->endDate]);
        }

        $total = (clone $query)->count();

        $query->orderBy($criteria->getOrderClause())
            ->limit($criteria->pageSize)
            ->offset($criteria->calculateOffset());

        $rows = iterator_to_array(
            $this->streamRows(
                query: $query, 
                jsonKeys: ['revision']
            )
        );

        $rows = array_map(
            static function (array $row): array {
                $row['period'] = isset($row['date']) && $row['date'] !== null
                    ? AttendancePeriod::fromDateString((string) $row['date'])->toInt()
                    : null;

                return $row;
            },
            $rows
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

    public function insert(AttendancePermission $entity): AttendancePermission 
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

            $newEntity = AttendancePermission::reconstitute(
                id: $newId,
                employeeId: $entity->getEmployeeId(),
                permissionType: $entity->getPermissionType(),
                date: $entity->getDate(),
                note: $entity->getNote(),
                approved: $entity->getApproved(),
                approvedNote: $entity->getApprovedNote(),
                revision: $entity->getRevision(),
                status: $entity->getStatus(),
                detailInfo: $entity->getDetailInfo(),
                lockVersion: LockVersion::create(),
            );

            $this->syncMongoDB(
                entity: $newEntity,
                schemaClass: MdbAttendancePermissionSchema::class
            );

            return $newEntity;
        });
    }

    public function update(AttendancePermission $entity): AttendancePermission
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
                schemaClass: MdbAttendancePermissionSchema::class
            );
            
            return $entity;
        });
    }

    public function delete(AttendancePermission $entity): AttendancePermission
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
                schemaClass: MdbAttendancePermissionSchema::class
            );

            return $deletedEntity;
        });
    }

    public function restore(int $id): ?AttendancePermission
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

        $entity = AttendancePermission::reconstitute(
            id: (int) $row['id'],
            // name: $row['name'],
            employeeId: $row['employee_id'],
            permissionType: $row['permission_type'],
            date: $row['date'],
            note: $row['note'],
            approved: $row['approved'],
            approvedNote: $row['approved_note'],
            status: ResourceStatus::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()])
        );

        $entity->restore();

        return $this->update(
            entity: $entity,
        );
    }

    private function mapEntityToTable(AttendancePermission $entity, int $lockVersion): array
    {
        return [
            // 'name' => $entity->getName(),
            'employee_id' => $entity->getEmployeeId(),
            'permission_type' => $entity->getPermissionType(),
            'date' => $entity->getDate(),
            'note' => $entity->getNote(),
            'approved' => $entity->getApproved(),
            'approved_note' => $entity->getApprovedNote(),
            'revision' => $entity->getRevision()?->toArray(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            SyncMdb::field() => $entity->getSyncMdbValue(),
            LockVersion::field() => $lockVersion,
        ];
    }
}