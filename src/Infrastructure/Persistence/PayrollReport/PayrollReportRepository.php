<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PayrollReport;

// Domain Layer
use App\Domain\PayrollReport\Entity\PayrollReport;
use App\Domain\PayrollReport\Repository\PayrollReportRepositoryInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;

// Infrastructure Layer
use App\Infrastructure\Concerns\HasCoreFeatures;
use App\Infrastructure\Concerns\HasMongoDBSync;
use App\Infrastructure\Concerns\ManagesPersistence;
use App\Infrastructure\Database\MongoDB\MongoDBService;
use App\Infrastructure\Persistence\PayrollReport\MdbPayrollReportSchema;
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

/**
 * PayrollReport Repository using Yiisoft/Db Query Builder
 *
 * Pure query implementation using Yiisoft/Db for database operations
 */
final class PayrollReportRepository implements PayrollReportRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures, HasMongoDBSync, ManagesPersistence;

    public const TABLE_NAME = 'payroll_report';
    public const SEQUENCE_ID = 'payroll_report_id_seq';
    private const LIKE_OPERATOR = 'ilike';

    /** @var list<string> */
    private const JSON_KEYS = ['employee', 'attendance_summary', 'payroll_summary', 'overtime_summary', 'payroll_net', 'detail_info'];

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
        return PayrollReport::RESOURCE;
    }

    public function findById(int $id, ?int $status = null): ?PayrollReport
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

    public function findByName(string $name, ?int $status = null): ?PayrollReport
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
     * @return list<PayrollReport>
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
            fn(array $row): PayrollReport => $this->hydrate($row),
            $rows
        );
    }

    public function findByAttendanceSummaryId(int $attendanceSummaryId, ?int $status = null): ?PayrollReport
    {
        /** @var array<string, mixed>|false $row */
        $row = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['=', new Expression("attendance_summary->>'id'"), (string) $attendanceSummaryId])
            ->andWhere($this->scopeWhereNotDeleted())
            ->andWhere($this->scopeByStatus($status))
            ->one();

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function list(SearchCriteria $criteria): PaginatedResult
    {
        $query = (new Query($this->db))
            ->select([
                'id',
                'name',
                'period',
                'employee',
                'attendance_summary',
                'payroll_summary',
                'overtime_summary',
                'payroll_net',
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
            ]
        );

        if (!empty($filter['name'])) {
            $this->queryConditionApplier->orLike(
                query: $query,
                operator: self::LIKE_OPERATOR,
                conditions: ['name' => '%' . $filter['name'] . '%']
            );
        }

        if (!empty($filter['employee_id'])) {
            $employeeId = (int) $filter['employee_id'];
            $query->andWhere(
                ['=', new Expression("employee->>'id'"), (string) $employeeId],
            );
        }

        if (!empty($filter['employee_name'])) {
            $employeeName = (string) $filter['employee_name'];
            $query->andWhere(
                ['ilike', new Expression("employee->>'name'"), '%' . $employeeName . '%'],
            );
        }

        $total = (clone $query)->count();

        $query->orderBy($criteria->getOrderClause())
            ->limit($criteria->pageSize)
            ->offset($criteria->calculateOffset());

        $rows = iterator_to_array(
            $this->streamRows(
                query: $query,
                jsonKeys: self::JSON_KEYS
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

    public function insert(PayrollReport $entity): PayrollReport
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

            $newEntity = PayrollReport::reconstitute(
                id: $newId,
                name: $entity->getName(),
                period: $entity->getPeriod(),
                employee: $entity->getEmployee()->toArray(),
                attendanceSummary: $entity->getAttendanceSummary()->toArray(),
                payrollSummary: $entity->getPayrollSummary()->toArray(),
                overtimeSummary: $entity->getOvertimeSummary()->toArray(),
                payrollNet: $entity->getPayrollNet()->toArray(),
                notes: $entity->getNotes(),
                status: $entity->getStatus(),
                detailInfo: $entity->getDetailInfo(),
                lockVersion: LockVersion::create(),
            );

            $this->syncMongoDB(
                entity: $newEntity,
                schemaClass: MdbPayrollReportSchema::class
            );

            return $newEntity;
        });
    }

    public function update(PayrollReport $entity): PayrollReport
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
                schemaClass: MdbPayrollReportSchema::class
            );

            return $entity;
        });
    }

    public function delete(PayrollReport $entity): PayrollReport
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
                schemaClass: MdbPayrollReportSchema::class
            );

            return $deletedEntity;
        });
    }

    public function restore(int $id): ?PayrollReport
    {
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
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): PayrollReport
    {
        return PayrollReport::reconstitute(
            id: (int) $row['id'],
            name: (string) $row['name'],
            period: $row['period'] !== null ? (int) $row['period'] : null,
            employee: $this->castToArray($row['employee'] ?? null),
            attendanceSummary: $this->castToArray($row['attendance_summary'] ?? null),
            payrollSummary: $this->castToArray($row['payroll_summary'] ?? null),
            overtimeSummary: $this->castToArray($row['overtime_summary'] ?? null),
            payrollNet: $this->castToArray($row['payroll_net'] ?? null),
            notes: $row['notes'] ?? null,
            status: ResourceStatus::from((int) $row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info'] ?? ''),
            syncMdb: SyncMdb::fromInt($row[SyncMdb::field()] ?? null),
            lockVersion: LockVersion::fromInt((int) ($row[LockVersion::field()] ?? LockVersion::DEFAULT_VALUE)),
        );
    }

    private function mapEntityToTable(PayrollReport $entity, int $lockVersion): array
    {
        return [
            'name' => $entity->getName(),
            'period' => $entity->getPeriod(),
            'employee' => $entity->getEmployee()->toArray(),
            'attendance_summary' => $entity->getAttendanceSummary()->toArray(),
            'payroll_summary' => $entity->getPayrollSummary()->toArray(),
            'overtime_summary' => $entity->getOvertimeSummary()->toArray(),
            'payroll_net' => $entity->getPayrollNet()->toArray(),
            'notes' => $entity->getNotes(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            SyncMdb::field() => $entity->getSyncMdbValue(),
            LockVersion::field() => $lockVersion,
        ];
    }
}
