<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Attendance;

// Domain Layer
use App\Domain\Attendance\Entity\Attendance;
use App\Domain\Attendance\Repository\AttendanceRepositoryInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;
use App\Domain\Attendance\ValueObject\AttendanceRecords;
use App\Domain\Attendance\ValueObject\DateRange;

// Infrastructure Layer
use App\Infrastructure\Concerns\HasCoreFeatures;
use App\Infrastructure\Concerns\HasMongoDBSync;
use App\Infrastructure\Concerns\ManagesPersistence;
use App\Infrastructure\Database\MongoDB\MongoDBService;
use App\Infrastructure\Persistence\Attendance\MdbAttendanceSchema;
use App\Infrastructure\Security\CurrentUserAwareInterface;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Query\QueryConditionApplier;
use App\Shared\ValueObject\Message;

// Vendor Layer
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;

// use MongoDB\Collection;

/**
 * Attendance Repository using Yiisoft/Db Query Builder
 * 
 * Pure query implementation using Yiisoft/Db for database operations
 */
final class AttendanceRepository implements AttendanceRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures, HasMongoDBSync, ManagesPersistence;

    public const TABLE_NAME = 'attendance';
    public const SEQUENCE_ID = 'attendance_id_seq';
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
        return Attendance::RESOURCE;
    }

    private function mapEntityToTable(Attendance $entity, int $lockVersion): array
    {
        return [
            'date' => $entity->getDate(),
            'period' => $entity->getPeriod(),
            'attendance' => json_encode($entity->getAttendance()),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            SyncMdb::field() => $entity->getSyncMdbValue(),
            LockVersion::field() => $lockVersion,
        ];
    }

    public function findById(int $id, ?int $status = null): ?Attendance
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

        return Attendance::reconstitute(
            id: (int) $row['id'],
            date: $row['date'],
            period: (int) $row['period'],
            attendance: $this->castToArray($row['attendance'] ?? null),
            status: ResourceStatus::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
            syncMdb: SyncMdb::fromInt($row[SyncMdb::field()] ?? null),
        );
    }

    public function findByName(string $name, ?int $status = null): ?Attendance
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

        return Attendance::reconstitute(
            id: (int) $row['id'],
            date: $row['date'],
            period: (int) $row['period'],
            attendance: $this->castToArray($row['attendance'] ?? null),
            status: ResourceStatus::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
            syncMdb: SyncMdb::fromInt($row[SyncMdb::field()] ?? null),
        );
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

    /**
     * @return Attendance[]
     */
    public function findByPeriod(int $period, ?int $status = null): array
    {
        /** @var array<int, array<string, mixed>> $rows */
        $rows = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['period' => $period])
            ->andWhere($this->scopeWhereNotDeleted())
            ->andWhere($this->scopeByStatus($status))
            ->all();

        return array_map(
            fn(array $row): Attendance => Attendance::reconstitute(
                id: (int) $row['id'],
                date: $row['date'],
                period: (int) $row['period'],
                attendance: $this->castToArray($row['attendance'] ?? null),
                status: ResourceStatus::from((int)$row['status']),
                detailInfo: DetailInfo::fromJson($row['detail_info']),
                syncMdb: SyncMdb::fromInt($row[SyncMdb::field()] ?? null),
                lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
            ),
            $rows
        );
    }

    public function list(SearchCriteria $criteria): PaginatedResult
    {
        $query = (new Query($this->db))
            ->select([
                'id',
                'date',
                'period',
                'attendance',
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
                'period',
                'attendance',
                'status', 
                SyncMdb::field(),
            ]
        );

        if (!empty($filter['employee_name'])) {
            $searchTerm = $filter['employee_name'];
            $query->andWhere([
                'EXISTS',
                (new Query($this->db))
                    ->from(new Expression("jsonb_array_elements(attendance) AS elem"))
                    ->where(['ilike', new Expression("elem->>'employee_name'"), "%$searchTerm%"])
            ]);
        }

        if (!empty($filter['name'])) {
            $this->queryConditionApplier->orLike(
                query: $query, 
                operator: self::LIKE_OPERATOR,
                conditions: ['name' => '%' . $filter['name'] . '%']
            );
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
                jsonKeys: ['attendance']
            )
        );

        $rows = array_map(
            static fn(array $row): array => self::sanitizeAttendanceRows($row),
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

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function sanitizeAttendanceRows(array $row): array
    {
        if (!is_array($row['attendance'] ?? null)) {
            return $row;
        }

        $row['attendance'] = array_map(
            static function (array $item): array {
                unset($item['attendance_policies']);

                if (array_key_exists('total_work_minutes', $item) && !array_key_exists('work_duration', $item)) {
                    $item['work_duration'] = $item['total_work_minutes'];
                }

                unset($item['total_work_minutes']);

                return $item;
            },
            $row['attendance']
        );

        return $row;
    }

    public function insert(Attendance $entity): Attendance 
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

            $newEntity = Attendance::reconstitute(
                id: $newId,
                date: $entity->getDate(),
                period: $entity->getPeriod(),
                attendance: $entity->getAttendance(),
                status: $entity->getStatus(),
                detailInfo: $entity->getDetailInfo(),
                lockVersion: LockVersion::create(),
            );

            $this->syncMongoDB(
                entity: $newEntity,
                schemaClass: MdbAttendanceSchema::class
            );

            return $newEntity;
        });
    }

    public function update(Attendance $entity): Attendance
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
                schemaClass: MdbAttendanceSchema::class
            );
            
            return $entity;
        });
    }

    public function delete(Attendance $entity): Attendance
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
                schemaClass: MdbAttendanceSchema::class
            );

            return $deletedEntity;
        });
    }

    public function restore(int $id): ?Attendance
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

        $entity = Attendance::reconstitute(
            id: (int) $row['id'],
            date: $row['date'],
            period: $row['period'],
            attendance: $row['attendance'],
            status: ResourceStatus::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()])
        );

        $entity->restore();

        return $this->update(
            entity: $entity,
        );
    }

    public function findByDate(string $date, ?int $status = null): ?Attendance
    {
        /** @var array<string, mixed>|false $row */
        $row = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where([
                'date' => $date,
            ])
            ->andWhere($this->scopeWhereNotDeleted())
            ->andWhere($this->scopeByStatus($status))
            ->one();

        if (!$row) {
            return null;
        }

        return Attendance::reconstitute(
            id: (int) $row['id'],
            date: $row['date'],
            period: (int) $row['period'],
            attendance: json_decode($row['attendance'], true),
            status: ResourceStatus::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
        );
    }

    public function findLatestActive(): ?Attendance
    {
        /** @var array<string, mixed>|false $row */
        $row = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where([
                'status' => ResourceStatus::active()->value(),
            ])
            ->andWhere($this->scopeWhereNotDeleted())
            ->orderBy(['date' => SORT_DESC])
            ->one();

        if (!$row) {
            return null;
        }

        return Attendance::reconstitute(
            id: (int) $row['id'],
            date: $row['date'],
            period: (int) $row['period'],
            attendance: json_decode($row['attendance'], true),
            status: ResourceStatus::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
        );
    }
}