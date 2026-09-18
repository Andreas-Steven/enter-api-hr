<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EmployeeDebtTransaction;

// Domain Layer
use App\Domain\EmployeeDebtTransaction\Entity\EmployeeDebtTransaction;
use App\Domain\EmployeeDebtTransaction\Repository\EmployeeDebtTransactionRepositoryInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;

// Infrastructure Layer
use App\Infrastructure\Concerns\HasCoreFeatures;
use App\Infrastructure\Concerns\HasMongoDBSync;
use App\Infrastructure\Concerns\ManagesPersistence;
use App\Infrastructure\Database\MongoDB\MongoDBService;
use App\Infrastructure\Persistence\EmployeeDebtTransaction\MdbEmployeeDebtTransactionSchema;
use App\Infrastructure\Security\CurrentUserAwareInterface;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Query\QueryConditionApplier;

// Vendor Layer
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * EmployeeDebtTransaction Repository using Yiisoft/Db Query Builder
 *
 * Pure query implementation using Yiisoft/Db for database operations
 */
final class EmployeeDebtTransactionRepository implements EmployeeDebtTransactionRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures, HasMongoDBSync, ManagesPersistence;

    public const TABLE_NAME = 'employee_debt_transaction';
    public const SEQUENCE_ID = 'employee_debt_transaction_id_seq';
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
        return EmployeeDebtTransaction::RESOURCE;
    }

    public function findById(int $id, ?int $status = null): ?EmployeeDebtTransaction
    {
        /** @var array<string, mixed>|false $row */
        $row = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['id' => $id])
            ->andWhere($this->scopeWhereNotDeleted())
            ->andWhere($this->scopeByStatus($status))
            ->one();

        if (!$row) {
            return null;
        }

        return $this->hydrateRow($row);
    }

    public function findByName(string $name, ?int $status = null): ?EmployeeDebtTransaction
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

        return $this->hydrateRow($row);
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

    public function findByEmployeeDebtId(int $employeeDebtId, ?int $status = null): array
    {
        $rows = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['employee_debt_id' => $employeeDebtId])
            ->andWhere($this->scopeWhereNotDeleted())
            ->andWhere($this->scopeByStatus($status))
            ->orderBy(['id' => SORT_ASC])
            ->all();

        return array_map(
            fn (array $row): EmployeeDebtTransaction => $this->hydrateRow($row),
            $rows
        );
    }

    public function existsForPayrollDeduction(int $payrollDeductionId, ?int $status = null): bool
    {
        return (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['payroll_deduction_id' => $payrollDeductionId])
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
                'employee_debt_id',
                'employee_id',
                'transaction_type',
                'date',
                'amount',
                'payment_source',
                'payroll_deduction_id',
                'payroll_period',
                'note',
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
                'employee_debt_id',
                'employee_id',
                'transaction_type',
                'payment_source',
                'payroll_deduction_id',
                'payroll_period',
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

        if (!empty($filter['note'])) {
            $this->queryConditionApplier->orLike(
                query: $query,
                operator: self::LIKE_OPERATOR,
                conditions: ['note' => '%' . $filter['note'] . '%']
            );
        }

        $total = (clone $query)->count();

        $query->orderBy($criteria->getOrderClause())
            ->limit($criteria->pageSize)
            ->offset($criteria->calculateOffset());

        $rows = iterator_to_array(
            $this->streamRows(
                query: $query,
                jsonKeys: []
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

    public function insert(EmployeeDebtTransaction $entity): EmployeeDebtTransaction
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

            $newEntity = EmployeeDebtTransaction::reconstitute(
                id: $newId,
                name: $entity->getName(),
                employeeDebtId: $entity->getEmployeeDebtId(),
                employeeId: $entity->getEmployeeId(),
                transactionType: $entity->getTransactionType(),
                date: $entity->getDate(),
                amount: $entity->getAmount(),
                paymentSource: $entity->getPaymentSource(),
                payrollDeductionId: $entity->getPayrollDeductionId(),
                payrollPeriod: $entity->getPayrollPeriod(),
                note: $entity->getNote(),
                status: $entity->getStatus(),
                detailInfo: $entity->getDetailInfo(),
                lockVersion: LockVersion::create(),
            );

            $this->syncMongoDB(
                entity: $newEntity,
                schemaClass: MdbEmployeeDebtTransactionSchema::class
            );

            return $newEntity;
        });
    }

    public function update(EmployeeDebtTransaction $entity): EmployeeDebtTransaction
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
                schemaClass: MdbEmployeeDebtTransactionSchema::class
            );

            return $entity;
        });
    }

    public function delete(EmployeeDebtTransaction $entity): EmployeeDebtTransaction
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
                schemaClass: MdbEmployeeDebtTransactionSchema::class
            );

            return $deletedEntity;
        });
    }

    public function restore(int $id): ?EmployeeDebtTransaction
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

        $entity = $this->hydrateRow($row);

        $entity->restore();

        return $this->update(
            entity: $entity,
        );
    }

    private function mapEntityToTable(EmployeeDebtTransaction $entity, int $lockVersion): array
    {
        return [
            'name' => $entity->getName(),
            'employee_debt_id' => $entity->getEmployeeDebtId(),
            'employee_id' => $entity->getEmployeeId(),
            'transaction_type' => $entity->getTransactionType(),
            'date' => $entity->getDate(),
            'amount' => $entity->getAmount(),
            'payment_source' => $entity->getPaymentSource(),
            'payroll_deduction_id' => $entity->getPayrollDeductionId(),
            'payroll_period' => $entity->getPayrollPeriod(),
            'note' => $entity->getNote(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            SyncMdb::field() => $entity->getSyncMdbValue(),
            LockVersion::field() => $lockVersion,
        ];
    }

    /**
     * Hydrate satu baris DB menjadi EmployeeDebtTransaction entity.
     */
    private function hydrateRow(array $row): EmployeeDebtTransaction
    {
        return EmployeeDebtTransaction::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            employeeDebtId: (int) $row['employee_debt_id'],
            employeeId: (int) $row['employee_id'],
            transactionType: $row['transaction_type'],
            date: $row['date'],
            amount: (int) $row['amount'],
            paymentSource: $row['payment_source'],
            payrollDeductionId: $row['payroll_deduction_id'] !== null ? (int) $row['payroll_deduction_id'] : null,
            payrollPeriod: $row['payroll_period'] !== null ? (int) $row['payroll_period'] : null,
            note: $row['note'] ?? null,
            status: ResourceStatus::from((int) $row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
        )->updateSyncMdb(SyncMdb::fromInt($row[SyncMdb::field()] ?? null));
    }
}
