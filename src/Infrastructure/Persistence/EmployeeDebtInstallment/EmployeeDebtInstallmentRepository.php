<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EmployeeDebtInstallment;

// Domain Layer
use App\Domain\EmployeeDebtInstallment\Entity\EmployeeDebtInstallment;
use App\Domain\EmployeeDebtInstallment\Repository\EmployeeDebtInstallmentRepositoryInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;

// Infrastructure Layer
use App\Infrastructure\Concerns\HasCoreFeatures;
use App\Infrastructure\Concerns\HasMongoDBSync;
use App\Infrastructure\Concerns\ManagesPersistence;
use App\Infrastructure\Database\MongoDB\MongoDBService;
use App\Infrastructure\Persistence\EmployeeDebtInstallment\MdbEmployeeDebtInstallmentSchema;
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
 * EmployeeDebtInstallment Repository using Yiisoft/Db Query Builder
 * 
 * Pure query implementation using Yiisoft/Db for database operations
 */
final class EmployeeDebtInstallmentRepository implements EmployeeDebtInstallmentRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures, HasMongoDBSync, ManagesPersistence;

    public const TABLE_NAME = 'employee_debt_installment';
    public const SEQUENCE_ID = 'employee_debt_installment_id_seq';
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
        return EmployeeDebtInstallment::RESOURCE;
    }

    public function findById(int $id, ?int $status = null): ?EmployeeDebtInstallment
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

        return $this->hydrateRow($row);
    }

    public function findByName(string $name, ?int $status = null): ?EmployeeDebtInstallment
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
            ->orderBy(['sequence' => SORT_ASC])
            ->all();

        return array_map(
            fn (array $row): EmployeeDebtInstallment => $this->hydrateRow($row),
            $rows
        );
    }

    public function findPendingByEmployeeDebtId(int $employeeDebtId, ?int $status = null): array
    {
        $rows = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where([
                'employee_debt_id' => $employeeDebtId,
                'payment_status' => 0,
            ])
            ->andWhere($this->scopeWhereNotDeleted())
            ->andWhere($this->scopeByStatus($status))
            ->orderBy(['sequence' => SORT_ASC])
            ->all();

        return array_map(
            fn (array $row): EmployeeDebtInstallment => $this->hydrateRow($row),
            $rows
        );
    }

    public function findDueForPeriod(string $period, ?int $status = null): array
    {
        // Periode YYYY-MM: due_date jatuh di bulan itu, payment_status = 0 (pending).
        $start = $period . '-01';
        $end = $period . '-' . date('t', strtotime($start));

        $rows = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['payment_status' => 0])
            ->andWhere(['>=', 'due_date', $start])
            ->andWhere(['<=', 'due_date', $end])
            ->andWhere($this->scopeWhereNotDeleted())
            ->andWhere($this->scopeByStatus($status))
            ->orderBy(['due_date' => SORT_ASC, 'sequence' => SORT_ASC])
            ->all();

        return array_map(
            fn (array $row): EmployeeDebtInstallment => $this->hydrateRow($row),
            $rows
        );
    }

    public function list(SearchCriteria $criteria): PaginatedResult
    {
        $query = (new Query($this->db))
            ->select([
                'id',
                'name',
                'employee_debt_id',
                'payroll_deduction_id',
                'sequence',
                'due_date',
                'amount',
                'payment_status',
                'paid_at',
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
                'payroll_deduction_id',
                'payment_status',
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

    public function insert(EmployeeDebtInstallment $entity): EmployeeDebtInstallment 
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

            $newEntity = EmployeeDebtInstallment::reconstitute(
                id: $newId,
                name: $entity->getName(),
                employeeDebtId: $entity->getEmployeeDebtId(),
                payrollDeductionId: $entity->getPayrollDeductionId(),
                sequence: $entity->getSequence(),
                dueDate: $entity->getDueDate(),
                amount: $entity->getAmount(),
                paymentStatus: $entity->getPaymentStatus(),
                paidAt: $entity->getPaidAt(),
                status: $entity->getStatus(),
                detailInfo: $entity->getDetailInfo(),
                lockVersion: LockVersion::create(),
            );

            $this->syncMongoDB(
                entity: $newEntity,
                schemaClass: MdbEmployeeDebtInstallmentSchema::class
            );

            return $newEntity;
        });
    }

    public function update(EmployeeDebtInstallment $entity): EmployeeDebtInstallment
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
                schemaClass: MdbEmployeeDebtInstallmentSchema::class
            );
            
            return $entity;
        });
    }

    public function delete(EmployeeDebtInstallment $entity): EmployeeDebtInstallment
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
                schemaClass: MdbEmployeeDebtInstallmentSchema::class
            );

            return $deletedEntity;
        });
    }

    public function restore(int $id): ?EmployeeDebtInstallment
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

        $entity = $this->hydrateRow($row);

        $entity->restore();

        return $this->update(
            entity: $entity,
        );
    }

    private function mapEntityToTable(EmployeeDebtInstallment $entity, int $lockVersion): array
    {
        return [
            'name' => $entity->getName(),
            'employee_debt_id' => $entity->getEmployeeDebtId(),
            'payroll_deduction_id' => $entity->getPayrollDeductionId(),
            'sequence' => $entity->getSequence(),
            'due_date' => $entity->getDueDate(),
            'amount' => $entity->getAmount(),
            'payment_status' => $entity->getPaymentStatus(),
            'paid_at' => $entity->getPaidAt(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            SyncMdb::field() => $entity->getSyncMdbValue(),
            LockVersion::field() => $lockVersion,
        ];
    }

    /**
     * Hydrate satu baris DB menjadi EmployeeDebtInstallment entity.
     * Digunakan bersama oleh findById / findByName / findByEmployeeDebtId / dst.
     */
    private function hydrateRow(array $row): EmployeeDebtInstallment
    {
        return EmployeeDebtInstallment::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            employeeDebtId: (int) $row['employee_debt_id'],
            payrollDeductionId: $row['payroll_deduction_id'] !== null ? (int) $row['payroll_deduction_id'] : null,
            sequence: (int) $row['sequence'],
            dueDate: $row['due_date'],
            amount: (float) $row['amount'],
            paymentStatus: (int) $row['payment_status'],
            paidAt: $row['paid_at'] ?? null,
            status: ResourceStatus::from((int) $row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
        )->updateSyncMdb($row[SyncMdb::field()] ?? null);
    }
}
