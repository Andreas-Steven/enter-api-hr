<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EmployeeLeave;

// Domain Layer
use App\Domain\EmployeeLeave\Entity\EmployeeLeave;
use App\Domain\EmployeeLeave\Repository\EmployeeLeaveRepositoryInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;

// Infrastructure Layer
use App\Infrastructure\Concerns\HasCoreFeatures;
use App\Infrastructure\Concerns\HasMongoDBSync;
use App\Infrastructure\Concerns\ManagesPersistence;
use App\Infrastructure\Database\MongoDB\MongoDBService;
use App\Infrastructure\Persistence\EmployeeLeave\MdbEmployeeLeaveSchema;
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
 * EmployeeLeave Repository using Yiisoft/Db Query Builder
 * 
 * Pure query implementation using Yiisoft/Db for database operations
 */
final class EmployeeLeaveRepository implements EmployeeLeaveRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures, HasMongoDBSync, ManagesPersistence;

    public const TABLE_NAME = 'employee_leave';
    public const SEQUENCE_ID = 'employee_leave_id_seq';
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
        return EmployeeLeave::RESOURCE;
    }

    public function findById(int $id, ?int $status = null): ?EmployeeLeave
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

        return $this->reconstituteFromRow($row);
    }

    public function findByName(string $name, ?int $status = null): ?EmployeeLeave
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

        return $this->reconstituteFromRow($row);
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
     * @return array<int, EmployeeLeave>
     */
    public function findByEmployeeAndPeriod(int $employeeId, int $period, ?int $status = null): array
    {
        [$periodStart, $periodEnd] = $this->attendancePeriodDateRange($period);

        $rows = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['employee_id' => $employeeId])
            ->andWhere($this->scopeWhereNotDeleted())
            ->andWhere($this->scopeByStatus($status))
            ->andWhere(['<=', 'period_start_date', $periodEnd])
            ->andWhere(['>=', 'period_end_date', $periodStart])
            ->orderBy([
                'period_start_date' => SORT_ASC,
                'id' => SORT_ASC,
            ])
            ->all();

        return array_map(
            fn(array $row): EmployeeLeave => $this->reconstituteFromRow($row),
            $rows,
        );
    }

    public function findByEmployeeTypeAndPeriod(
        int $employeeId,
        string $leaveType,
        string $periodStartDate,
        string $periodEndDate,
        ?int $status = null,
    ): ?EmployeeLeave {
        $row = (new Query($this->db))
            ->select([
                'id',
                'name',
                'leave_type',
                'employee_id',
                'period_start_date',
                'period_end_date',
                'initial_amount',
                'adjustment_amount',
                'used_amount',
                'remaining_amount',
                'leave_records',
                'adjustment_records',
                'status',
                'detail_info',
                SyncMdb::field(),
                LockVersion::field(),
            ])
            ->from(self::TABLE_NAME)
            ->where(['employee_id' => $employeeId])
            ->andWhere(['leave_type' => $leaveType])
            ->andWhere(['period_start_date' => $periodStartDate])
            ->andWhere(['period_end_date' => $periodEndDate])
            ->andWhere($this->scopeWhereNotDeleted())
            ->andWhere($this->scopeByStatus($status))
            ->orderBy(['id' => SORT_ASC])
            ->one();

        return $row === null ? null : $this->reconstituteFromRow($row);
    }

    public function list(SearchCriteria $criteria): PaginatedResult
    {
        $query = (new Query($this->db))
            ->select([
                'id',
                'name',
                'leave_type',
                'employee_id',
                'period_start_date',
                'period_end_date',
                'initial_amount',
                'adjustment_amount',
                'used_amount',
                'remaining_amount',
                'leave_records',
                'adjustment_records',
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

        if (!empty($filter['leave_type'])) {
            $this->queryConditionApplier->orLike(
                query: $query, 
                operator: self::LIKE_OPERATOR,
                conditions: ['leave_type' => '%' . $filter['leave_type'] . '%']
            );
        }

        $total = (clone $query)->count();

        $query->orderBy($criteria->getOrderClause())
            ->limit($criteria->pageSize)
            ->offset($criteria->calculateOffset());

        $rows = iterator_to_array(
            $this->streamRows(
                query: $query,
                jsonKeys: ['leave_records', 'adjustment_records']
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

    public function insert(EmployeeLeave $entity): EmployeeLeave 
    {
        return $this->db->transaction(function() use ($entity) {
            $lockVersion = LockVersion::create();

            $this->db->createCommand()
                ->insert(self::TABLE_NAME, 
                    $this->mapEntityToTable(
                        entity: $entity, 
                        lockVersion: $lockVersion->value()
                    )
                )
                ->execute();

            $newId = (int) $this->db->getLastInsertID(self::SEQUENCE_ID);

            $newEntity = EmployeeLeave::reconstitute(
                id: $newId,
                name: $entity->getName(),
                leaveType: $entity->getLeaveType(),
                employeeId: $entity->getEmployeeId(),
                periodStartDate: $entity->getPeriodStartDate(),
                periodEndDate: $entity->getPeriodEndDate(),
                initialAmount: $entity->getInitialAmount(),
                usedAmount: $entity->getUsedAmount(),
                leaveRecords: $entity->getLeaveRecords(),
                adjustmentRecords: $entity->getAdjustmentRecords(),
                status: $entity->getStatus(),
                detailInfo: $entity->getDetailInfo(),
                syncMdb: $entity->getSyncMdb(),
                lockVersion: $lockVersion,
            );

            $this->syncMongoDB(
                entity: $newEntity,
                schemaClass: MdbEmployeeLeaveSchema::class
            );

            return $newEntity;
        });
    }

    public function update(EmployeeLeave $entity): EmployeeLeave
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
                schemaClass: MdbEmployeeLeaveSchema::class
            );
            
            return $entity;
        });
    }

    public function delete(EmployeeLeave $entity): EmployeeLeave
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
                schemaClass: MdbEmployeeLeaveSchema::class
            );

            return $deletedEntity;
        });
    }

    public function restore(int $id): ?EmployeeLeave
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

        $entity = $this->reconstituteFromRow($row);

        $entity->restore();

        return $this->update(
            entity: $entity,
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function attendancePeriodDateRange(int $period): array
    {
        $year = intdiv($period, 100);
        $month = $period % 100;
        if ($year < 1 || $month < 1 || $month > 12) {
            throw new \InvalidArgumentException(sprintf('Invalid attendance period: %d', $period));
        }

        $periodEnd = \DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            sprintf('%04d-%02d-25', $year, $month),
        );

        if ($periodEnd === false) {
            throw new \InvalidArgumentException(sprintf('Invalid attendance period: %d', $period));
        }

        return [
            $periodEnd->modify('-1 month')->modify('+1 day')->format('Y-m-d'),
            $periodEnd->format('Y-m-d'),
        ];
    }

    private function mapEntityToTable(EmployeeLeave $entity, int $lockVersion): array
    {
        return [
            'name' => $entity->getName(),
            'leave_type' => $entity->getLeaveType(),
            'employee_id' => $entity->getEmployeeId(),
            'period_start_date' => $entity->getPeriodStartDate(),
            'period_end_date' => $entity->getPeriodEndDate(),
            'initial_amount' => $entity->getInitialAmount(),
            'adjustment_amount' => $entity->getAdjustmentAmount(),
            'used_amount' => $entity->getUsedAmount(),
            'remaining_amount' => $entity->getRemainingAmount(),
            'leave_records' => $entity->getLeaveRecords(),
            'adjustment_records' => $entity->getAdjustmentRecords(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            SyncMdb::field() => $entity->getSyncMdbValue(),
            LockVersion::field() => $lockVersion,
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function reconstituteFromRow(array $row): EmployeeLeave
    {
        return EmployeeLeave::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            leaveType: $row['leave_type'],
            employeeId: (int) $row['employee_id'],
            periodStartDate: $row['period_start_date'],
            periodEndDate: $row['period_end_date'],
            initialAmount: (int) $row['initial_amount'],
            usedAmount: $row['used_amount'] !== null ? (int) $row['used_amount'] : null,
            leaveRecords: is_string($row['leave_records'])
                ? (json_decode($row['leave_records'], true) ?? [])
                : ($row['leave_records'] ?? []),
            adjustmentRecords: is_string($row['adjustment_records'] ?? null)
                ? (json_decode($row['adjustment_records'], true) ?? [])
                : ($row['adjustment_records'] ?? []),
            status: ResourceStatus::from((int) $row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            syncMdb: SyncMdb::fromInt($row[SyncMdb::field()] ?? null),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
        );
    }
}
