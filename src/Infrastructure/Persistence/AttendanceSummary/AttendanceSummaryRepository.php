<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\AttendanceSummary;

// Domain Layer
use App\Domain\AttendanceSummary\Entity\AttendanceSummary;
use App\Domain\AttendanceSummary\Repository\AttendanceSummaryRepositoryInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;
// Infrastructure Layer
use App\Infrastructure\Concerns\HasCoreFeatures;
use App\Infrastructure\Concerns\HasMongoDBSync;
use App\Infrastructure\Concerns\ManagesPersistence;
use App\Infrastructure\Database\MongoDB\MongoDBService;
use App\Infrastructure\Security\CurrentUserAwareInterface;
// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Query\QueryConditionApplier;
// Vendor Layer
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * AttendanceSummary Repository using Yiisoft/Db Query Builder
 *
 * Pure query implementation using Yiisoft/Db for database operations.
 */
final class AttendanceSummaryRepository implements AttendanceSummaryRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures;
    use HasMongoDBSync;
    use ManagesPersistence;

    public const TABLE_NAME = 'attendance_summary';
    public const SEQUENCE_ID = 'attendance_summary_id_seq';
    private const LIKE_OPERATOR = 'ilike';

    /** @var list<string> */
    private const JSON_KEYS = [
        'employee',
        'work_schedule',
        'attendance',
        'permissions',
        'summary',
        'policy',
    ];

    public function __construct(
        private ConnectionInterface $db,
        private QueryConditionApplier $queryConditionApplier,
        private MongoDBService $mongoDBService,
    ) {
        $this->initMongoDBSync(
            service: $mongoDBService,
            collection: self::TABLE_NAME,
        );
    }

    public function getResource(): string
    {
        return AttendanceSummary::RESOURCE;
    }

    public function findById(int $id, ?int $status = null): ?AttendanceSummary
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

        return $this->hydrate($row);
    }

    public function findByName(string $name, ?int $status = null): ?AttendanceSummary
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

        return $this->hydrate($row);
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
     * @return list<AttendanceSummary>
     */
    public function findByPeriod(int $period, ?int $status = null): array
    {
        /** @var array<int, array<string, mixed>> $rows */
        $rows = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['period' => $period])
            ->andWhere($this->scopeWhereNotDeleted())
            ->andWhere($this->scopeByStatus($status))
            ->orderBy(['id' => SORT_ASC])
            ->all();

        return array_map(
            fn(array $row): AttendanceSummary => $this->hydrate($row),
            $rows
        );
    }

    public function list(SearchCriteria $criteria): PaginatedResult
    {
        $query = (new Query($this->db))
            ->select([
                'id',
                'name',
                'period',
                'employee',
                'work_schedule',
                'attendance',
                'permissions',
                'summary',
                'policy',
                'notes',
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
                'status',
                SyncMdb::field(),
            ],
        );

        if (!empty($filter['name'])) {
            $this->queryConditionApplier->orLike(
                query: $query,
                operator: self::LIKE_OPERATOR,
                conditions: ['name' => '%' . $filter['name'] . '%'],
            );
        }

        if (!empty($filter['employee_id'])) {
            $employeeId = (int) $filter['employee_id'];
            $query->andWhere(
                ['=', new \Yiisoft\Db\Expression\Expression("employee->>'id'"), (string) $employeeId],
            );
        }

        if (!empty($filter['employee_name'])) {
            $employeeName = (string) $filter['employee_name'];
            $query->andWhere(
                ['ilike', new \Yiisoft\Db\Expression\Expression("employee->>'name'"), '%' . $employeeName . '%'],
            );
        }

        $total = (clone $query)->count();

        $query->orderBy($criteria->getOrderClause())
            ->limit($criteria->pageSize)
            ->offset($criteria->calculateOffset());

        $rows = iterator_to_array(
            $this->streamRows(
                query: $query,
                jsonKeys: self::JSON_KEYS,
            ),
        );

        return new PaginatedResult(
            data: $rows,
            total: $total,
            page: $criteria->page,
            pageSize: $criteria->pageSize,
            filter: $criteria->filter,
            sort: [
                'by' => $criteria->sortBy,
                'dir' => $criteria->sortDir,
            ],
        );
    }

    public function insert(AttendanceSummary $entity): AttendanceSummary
    {
        return $this->db->transaction(function () use ($entity) {
            $this->db->createCommand()
                ->insert(
                    self::TABLE_NAME,
                    $this->mapEntityToTable(
                        entity: $entity,
                        lockVersion: LockVersion::create()->value(),
                    ),
                )
                ->execute();

            $newId = (int) $this->db->getLastInsertID(self::SEQUENCE_ID);

            $newEntity = AttendanceSummary::reconstitute(
                id: $newId,
                name: $entity->getName(),
                period: $entity->getPeriod(),
                employee: $entity->getEmployee()->toArray(),
                workSchedule: $entity->getWorkSchedule()->toArray(),
                attendance: $entity->getAttendance()->toArray(),
                permissions: $entity->getPermissions()->toArray(),
                summary: $entity->getSummary()->toArray(),
                policy: $entity->getPolicy()->toArray(),
                notes: $entity->getNotes(),
                status: $entity->getStatus(),
                detailInfo: $entity->getDetailInfo(),
                syncMdb: $entity->getSyncMdb(),
                lockVersion: LockVersion::create(),
            );

            $this->syncMongoDB(
                entity: $newEntity,
                schemaClass: MdbAttendanceSummarySchema::class,
            );

            return $newEntity;
        });
    }

    public function update(AttendanceSummary $entity): AttendanceSummary
    {
        return $this->db->transaction(function () use ($entity) {
            $currentLock = $entity->getLockVersion();
            $newLock = $this->upgradeEntityLockVersion($entity);

            $result = $this->db->createCommand()
                ->update(
                    self::TABLE_NAME,
                    $this->mapEntityToTable(
                        entity: $entity,
                        lockVersion: $newLock->value(),
                    ),
                    $this->buildLockCondition(
                        entity: $entity,
                        currentLockVersion: $currentLock->value(),
                    ),
                )
                ->execute();

            if ($result === 0) {
                $this->handlePersistenceFailure($entity);
            }

            $this->syncMongoDB(
                entity: $entity,
                schemaClass: MdbAttendanceSummarySchema::class,
            );

            return $entity;
        });
    }

    public function delete(AttendanceSummary $entity): AttendanceSummary
    {
        return $this->db->transaction(function () use ($entity) {
            $result = $this->db->createCommand()
                ->update(
                    self::TABLE_NAME,
                    $this->getDeletedState(),
                    $this->buildSimpleCondition($entity),
                )
                ->execute();

            if ($result === 0) {
                $this->handlePersistenceFailure($entity, false);
            }

            $deletedEntity = $entity->markAsDeleted();

            $this->syncMongoDB(
                entity: $deletedEntity,
                schemaClass: MdbAttendanceSummarySchema::class,
            );

            return $deletedEntity;
        });
    }

    public function restore(int $id): ?AttendanceSummary
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

        $entity = $this->hydrate($row);
        $entity->restore();

        return $this->update(
            entity: $entity,
        );
    }

    /**
     * Hydrates a single DB row into an AttendanceSummary entity.
     *
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): AttendanceSummary
    {
        return AttendanceSummary::reconstitute(
            id: (int) $row['id'],
            name: (string) $row['name'],
            period: $row['period'] !== null ? (int) $row['period'] : null,
            employee: $this->castToArray($row['employee'] ?? null),
            workSchedule: $this->castToArray($row['work_schedule'] ?? null),
            attendance: $this->castToArray($row['attendance'] ?? null),
            permissions: $this->castToArray($row['permissions'] ?? null),
            summary: $this->castToArray($row['summary'] ?? null),
            policy: $this->castToArray($row['policy'] ?? null),
            notes: $row['notes'] ?? null,
            status: ResourceStatus::from((int) $row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info'] ?? ''),
            syncMdb: SyncMdb::fromInt($row[SyncMdb::field()] ?? null),
            lockVersion: LockVersion::fromInt((int) ($row[LockVersion::field()] ?? LockVersion::DEFAULT_VALUE)),
        );
    }

    private function mapEntityToTable(AttendanceSummary $entity, int $lockVersion): array
    {
        return [
            'name' => $entity->getName(),
            'period' => $entity->getPeriod(),
            'employee' => json_encode($entity->getEmployee()->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'work_schedule' => json_encode($entity->getWorkSchedule()->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'attendance' => json_encode($entity->getAttendance()->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'permissions' => json_encode($entity->getPermissions()->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'summary' => json_encode($entity->getSummary()->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'policy' => json_encode($entity->getPolicy()->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'notes' => $entity->getNotes(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            SyncMdb::field() => $entity->getSyncMdbValue(),
            LockVersion::field() => $lockVersion,
        ];
    }
}
