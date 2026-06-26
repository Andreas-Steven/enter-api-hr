<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\AttendanceCalendar;

// Domain Layer
use App\Domain\AttendanceCalendar\Entity\AttendanceCalendar;
use App\Domain\AttendanceCalendar\Repository\AttendanceCalendarRepositoryInterface;
use App\Domain\AttendanceCalendar\ValueObject\Scope;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;

// Infrastructure Layer
use App\Infrastructure\Concerns\HasCoreFeatures;
use App\Infrastructure\Concerns\HasMongoDBSync;
use App\Infrastructure\Concerns\ManagesPersistence;
use App\Infrastructure\Database\MongoDB\MongoDBService;
use App\Infrastructure\Persistence\AttendanceCalendar\MdbAttendanceCalendarSchema;
use App\Infrastructure\Security\CurrentUserAwareInterface;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Query\QueryConditionApplier;
use App\Shared\ValueObject\Message;

// Vendor Layer
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Expression\Expression;

// use MongoDB\Collection;

/**
 * AttendanceCalendar Repository using Yiisoft/Db Query Builder
 * 
 * Pure query implementation using Yiisoft/Db for database operations
 */
final class AttendanceCalendarRepository implements AttendanceCalendarRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures, HasMongoDBSync, ManagesPersistence;

    public const TABLE_NAME = 'attendance_calendar';
    public const SEQUENCE_ID = 'attendance_calendar_id_seq';
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
        return AttendanceCalendar::RESOURCE;
    }

    public function findById(int $id, ?int $status = null): ?AttendanceCalendar
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

        return AttendanceCalendar::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            period: $row['period'],
            date: $row['date'],
            holidayType: $row['holiday_type'],
            scope: Scope::fromJson($row['scope']),
            status: ResourceStatus::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
            note: $row['note'] ?? null,
            dayOff: (int) $row['day_off'],
        );
    }

    public function findByName(string $name, ?int $status = null): ?AttendanceCalendar
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

        return AttendanceCalendar::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            period: $row['period'],
            date: $row['date'],
            holidayType: $row['holiday_type'],
            scope: Scope::fromJson($row['scope']),
            status: ResourceStatus::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
            note: $row['note'] ?? null,
            dayOff: (int) $row['day_off'],
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

    public function findConflictingBranchIds(string $date, array $branchIds, ?int $excludeId = null): array
    {
        if (empty($branchIds)) {
            return [];
        }

        $branchIds = array_values(array_unique(array_map(static fn(int $id) => (int) $id, $branchIds)));

        $branchIdExpression = new Expression("(branch_elements->>'id')::int");

        $query = (new Query($this->db))
            ->select([$branchIdExpression->expression . ' AS branch_id'])
            ->from(self::TABLE_NAME)
            ->innerJoin(
                new Expression('jsonb_array_elements(scope) AS branch_elements'),
                'TRUE'
            )
            ->where(['date' => $date])
            ->andWhere($this->scopeWhereNotDeleted());

        if ($excludeId !== null) {
            $query->andWhere(['<>', 'id', $excludeId]);
        }

        $rows = $query
            ->andWhere(['in', $branchIdExpression, $branchIds])
            ->column();

        if (empty($rows)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $rows)));
    }

    public function list(SearchCriteria $criteria): PaginatedResult
    {
        $query = (new Query($this->db))
            ->select([
                'id',
                'name',
                'period',
                'date',
                'holiday_type',
                'scope',
                'note',
                'day_off',
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

        if (!empty($filter['date'])) {
            $this->queryConditionApplier->orLike(
                query: $query, 
                operator: self::LIKE_OPERATOR,
                conditions: ['date' => '%' . $filter['date'] . '%']
            );
        }

        if (!empty($filter['holiday_type'])) {
            $this->queryConditionApplier->orLike(
                query: $query,
                operator: self::LIKE_OPERATOR,
                conditions: ['holiday_type' => '%' . $filter['holiday_type'] . '%']
            );
        }

        if (!empty($filter['id'])) {
            $branchId = (string) $filter['id'];
            $query->andWhere(['like', 'scope', '%"id":' . $branchId . '%', false]);
        }

        if (!empty($filter['day_off'])) {
            $query->andWhere(['day_off' => $filter['day_off']]);
        }

        $total = (clone $query)->count();

        $query->orderBy($criteria->getOrderClause())
            ->limit($criteria->pageSize)
            ->offset($criteria->calculateOffset());

        $rows = iterator_to_array(
            $this->streamRows(
                query: $query, 
                jsonKeys: ['scope']
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

    public function insert(AttendanceCalendar $entity): AttendanceCalendar 
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

            $newEntity = AttendanceCalendar::reconstitute(
                id: $newId,
                name: $entity->getName(),
                period: $entity->getPeriod()->toInt(),
                date: $entity->getDate(),
                holidayType: $entity->getHolidayType(),
                scope: $entity->getScope(),
                status: $entity->getStatus(),
                detailInfo: $entity->getDetailInfo(),
                lockVersion: LockVersion::create(),
                note: $entity->getNote(),
                dayOff: $entity->getDayOff(),
            );

            $this->syncMongoDB(
                entity: $newEntity,
                schemaClass: MdbAttendanceCalendarSchema::class
            );

            return $newEntity;
        });
    }

    public function update(AttendanceCalendar $entity): AttendanceCalendar
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
                schemaClass: MdbAttendanceCalendarSchema::class
            );
            
            return $entity;
        });
    }

    public function delete(AttendanceCalendar $entity): AttendanceCalendar
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
                schemaClass: MdbAttendanceCalendarSchema::class
            );

            return $deletedEntity;
        });
    }

    public function restore(int $id): ?AttendanceCalendar
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

        $entity = AttendanceCalendar::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            period: $row['period'],
            date: $row['date'],
            holidayType: $row['holiday_type'],
            scope: Scope::fromJson($row['scope']),
            status: ResourceStatus::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
            note: $row['note'] ?? null,
            dayOff: (int)($row['day_off'] ?? 0),
        );

        $entity->restore();

        return $this->update(
            entity: $entity,
        );
    }

    private function mapEntityToTable(AttendanceCalendar $entity, int $lockVersion): array
    {
        return [
            'name' => $entity->getName(),
            'period' => $entity->getPeriod()->toInt(),
            'date' => $entity->getDate(),
            'holiday_type' => $entity->getHolidayType(),
            'scope' => $entity->getScope()->toArray(),
            'note' => $entity->getNote(),
            'day_off' => $entity->getDayOff(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            SyncMdb::field() => $entity->getSyncMdbValue(),
            LockVersion::field() => $lockVersion,
        ];
    }
}
