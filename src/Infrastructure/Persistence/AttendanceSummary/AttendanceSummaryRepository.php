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
use App\Infrastructure\Persistence\AttendanceSummary\MdbAttendanceSummarySchema;
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
 * AttendanceSummary Repository using Yiisoft/Db Query Builder
 * 
 * Pure query implementation using Yiisoft/Db for database operations
 */
final class AttendanceSummaryRepository implements AttendanceSummaryRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures, HasMongoDBSync, ManagesPersistence;

    public const TABLE_NAME = 'attendance_summary';
    public const SEQUENCE_ID = 'attendance_summary_id_seq';
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

        return $this->hydrateEntity($row);
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

        return $this->hydrateEntity($row)->updateSyncMdb($row[SyncMdb::field()] ?? null);
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
     * @return array<int, AttendanceSummary>
     */
    public function findByPeriod(int $period, ?int $status = null): array
    {
        $rows = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['period' => $period])
            ->andWhere($this->scopeWhereNotDeleted())
            ->andWhere($this->scopeByStatus($status))
            ->all();

        $entities = [];
        foreach ($rows as $row) {
            $entities[] = $this->hydrateEntity($row);
        }

        return $entities;
    }

    public function list(SearchCriteria $criteria): PaginatedResult
    {
        $query = (new Query($this->db))
            ->select([
                'id',
                'name',
                'employee_id',
                'period',
                'period_summary',
                'time_summary',
                'status',
                'detail_info',
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
                'branch_id',
                'division_id',
                'position_id',
                'employee_type_id',
                'work_pattern_id',
                'period',
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
                jsonKeys: ['period_summary', 'time_summary', 'detail_info']
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

    public function insert(AttendanceSummary $entity): AttendanceSummary 
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

            $newEntity = AttendanceSummary::reconstitute(
                id: $newId,
                name: $entity->getName(),
                employeeId: $entity->getEmployeeId(),
                branchId: $entity->getBranchId(),
                divisionId: $entity->getDivisionId(),
                positionId: $entity->getPositionId(),
                employeeTypeId: $entity->getEmployeeTypeId(),
                workPatternId: $entity->getWorkPatternId(),
                period: $entity->getPeriod(),
                attendanceDetail: $entity->getAttendanceDetail(),
                permissionDetail: $entity->getPermissionDetail(),
                periodSummary: $entity->getPeriodSummary(),
                timeSummary: $entity->getTimeSummary(),
                notes: $entity->getNotes(),
                status: $entity->getStatus(),
                detailInfo: $entity->getDetailInfo(),
                lockVersion: LockVersion::create(),
            );

            $this->syncMongoDB(
                entity: $newEntity,
                schemaClass: MdbAttendanceSummarySchema::class
            );

            return $newEntity;
        });
    }

    public function update(AttendanceSummary $entity): AttendanceSummary
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
                schemaClass: MdbAttendanceSummarySchema::class
            );
            
            return $entity;
        });
    }

    public function delete(AttendanceSummary $entity): AttendanceSummary
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
                schemaClass: MdbAttendanceSummarySchema::class
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

        $entity = AttendanceSummary::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            employeeId: (int) $row['employee_id'],
            branchId: (int) $row['branch_id'],
            divisionId: (int) $row['division_id'],
            positionId: (int) $row['position_id'],
            employeeTypeId: (int) $row['employee_type_id'],
            workPatternId: $row['work_pattern_id'] !== null ? (int) $row['work_pattern_id'] : null,
            period: $row['period'] !== null ? (int) $row['period'] : null,
            attendanceDetail: $this->parseJsonColumn($row, 'attendance_detail'),
            permissionDetail: $this->parseJsonColumn($row, 'permission_detail'),
            periodSummary: $this->parseJsonColumn($row, 'period_summary'),
            timeSummary: $this->parseJsonColumn($row, 'time_summary'),
            notes: $row['notes'],
            status: ResourceStatus::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()])
        );

        $entity->restore();

        return $this->update(
            entity: $entity,
        );
    }

    private function hydrateEntity(array $row): AttendanceSummary
    {
        return AttendanceSummary::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            employeeId: (int) $row['employee_id'],
            branchId: (int) $row['branch_id'],
            divisionId: (int) $row['division_id'],
            positionId: (int) $row['position_id'],
            employeeTypeId: (int) $row['employee_type_id'],
            workPatternId: $row['work_pattern_id'] !== null ? (int) $row['work_pattern_id'] : null,
            period: $row['period'] !== null ? (int) $row['period'] : null,
            attendanceDetail: $this->parseJsonColumn($row, 'attendance_detail'),
            permissionDetail: $this->parseJsonColumn($row, 'permission_detail'),
            periodSummary: $this->parseJsonColumn($row, 'period_summary'),
            timeSummary: $this->parseJsonColumn($row, 'time_summary'),
            notes: $row['notes'],
            status: ResourceStatus::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
        )->updateSyncMdb($row[SyncMdb::field()] ?? null);
    }

    private function parseJsonColumn(array $row, string $key): array
    {
        $value = $row[$key] ?? null;
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        return is_array($value) ? $value : [];
    }

    private function mapEntityToTable(AttendanceSummary $entity, int $lockVersion): array
    {
        return [
            'name' => $entity->getName(),
            'employee_id' => $entity->getEmployeeId(),
            'branch_id' => $entity->getBranchId(),
            'division_id' => $entity->getDivisionId(),
            'position_id' => $entity->getPositionId(),
            'employee_type_id' => $entity->getEmployeeTypeId(),
            'work_pattern_id' => $entity->getWorkPatternId(),
            'period' => $entity->getPeriod(),
            'attendance_detail' => json_encode($entity->getAttendanceDetail()),
            'permission_detail' => json_encode($entity->getPermissionDetail()),
            'period_summary' => json_encode($entity->getPeriodSummary()),
            'time_summary' => json_encode($entity->getTimeSummary()),
            'notes' => $entity->getNotes(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            SyncMdb::field() => $entity->getSyncMdbValue(),
            LockVersion::field() => $lockVersion,
        ];
    }
}
