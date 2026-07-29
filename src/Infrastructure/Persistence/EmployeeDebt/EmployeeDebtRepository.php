<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EmployeeDebt;

// Domain Layer
use App\Domain\EmployeeDebt\Entity\EmployeeDebt;
use App\Domain\EmployeeDebt\Repository\EmployeeDebtRepositoryInterface;
use App\Domain\EmployeeDebt\ValueObject\DateRange;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;

// Infrastructure Layer
use App\Infrastructure\Concerns\HasCoreFeatures;
use App\Infrastructure\Concerns\HasMongoDBSync;
use App\Infrastructure\Concerns\ManagesPersistence;
use App\Infrastructure\Database\MongoDB\MongoDBService;
use App\Infrastructure\Persistence\EmployeeDebt\MdbEmployeeDebtSchema;
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
 * EmployeeDebt Repository using Yiisoft/Db Query Builder
 * 
 * Pure query implementation using Yiisoft/Db for database operations
 */
final class EmployeeDebtRepository implements EmployeeDebtRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures, HasMongoDBSync, ManagesPersistence;

    public const TABLE_NAME = 'employee_debt';
    public const SEQUENCE_ID = 'employee_debt_id_seq';
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
        return EmployeeDebt::RESOURCE;
    }

    public function findById(int $id, ?int $status = null): ?EmployeeDebt
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

        return EmployeeDebt::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            employeeId: (int) $row['employee_id'],
            deductionTypeId: (int) $row['deduction_type_id'],
            amount: (float) $row['amount'],
            date: $row['date'],
            note: $row['note'],
            paymentTerm: (int) $row['payment_term'],
            paymentMethod: $row['payment_method'],
            installmentCount: (int) $row['installment_count'],
            installmentAmount: (float) $row['installment_amount'],
            remainingAmount: (float) $row['remaining_amount'],
            paidAmount: (float) $row['paid_amount'],
            firstPaymentDate: $row['first_payment_date'],
            isSettled: (int) $row['is_settled'],
            settledAt: $row['settled_at'],
            status: ResourceStatus::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
        );
    }

    public function findByName(string $name, ?int $status = null): ?EmployeeDebt
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

        return EmployeeDebt::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            employeeId: (int) $row['employee_id'],
            deductionTypeId: (int) $row['deduction_type_id'],
            amount: (float) $row['amount'],
            date: $row['date'],
            note: $row['note'],
            paymentTerm: (int) $row['payment_term'],
            paymentMethod: $row['payment_method'],
            installmentCount: (int) $row['installment_count'],
            installmentAmount: (float) $row['installment_amount'],
            remainingAmount: (float) $row['remaining_amount'],
            paidAmount: (float) $row['paid_amount'],
            firstPaymentDate: $row['first_payment_date'],
            isSettled: (int) $row['is_settled'],
            settledAt: $row['settled_at'],
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
                'name',
                'employee_id',
                'deduction_type_id',
                'amount',
                'date',
                'note',
                'payment_term',
                'payment_method',
                'installment_count',
                'installment_amount',
                'remaining_amount',
                'paid_amount',
                'first_payment_date',
                'is_settled',
                'settled_at',
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
                'deduction_type_id',
                'status',
                'payment_term',
                'payment_method',
                'installment_count',
                'is_settled',
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

        if (!empty($filter['amount'])) {
            $this->queryConditionApplier->orLike(
                query: $query, 
                operator: self::LIKE_OPERATOR,
                conditions: ['amount' => '%' . $filter['amount'] . '%']
            );
        }

        if (!empty($filter['note'])) {
            $this->queryConditionApplier->orLike(
                query: $query, 
                operator: self::LIKE_OPERATOR,
                conditions: ['note' => '%' . $filter['note'] . '%']
            );
        }

        // Date range fields
        if (!empty($filter['date'])) {
            $range = DateRange::fromFilterString($filter['date']);
            $query->andWhere(['>=', 'date', $range->startDate])
                  ->andWhere(['<=', 'date', $range->endDate]);
        }

        // Detail Info fields
        if (!empty($filter['employee_name'])) {
            $searchTerm = '%' . $filter['employee_name'] . '%';

            $query->andWhere([
                'ilike',
                new Expression("detail_info->'employee'->>'name'"),
                $searchTerm
            ]);
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

    public function insert(EmployeeDebt $entity): EmployeeDebt 
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

            $newEntity = EmployeeDebt::reconstitute(
                id: $newId,
                name: $entity->getName(),
                employeeId: $entity->getEmployeeId(),
                deductionTypeId: $entity->getDeductionTypeId(),
                amount: $entity->getAmount(),
                date: $entity->getDate(),
                note: $entity->getnote(),
                paymentTerm: $entity->getPaymentTerm(),
                paymentMethod: $entity->getPaymentMethod(),
                installmentCount: $entity->getInstallmentCount(),
                installmentAmount: $entity->getInstallmentAmount(),
                remainingAmount: $entity->getRemainingAmount(),
                paidAmount: $entity->getPaidAmount(),
                firstPaymentDate: $entity->getFirstPaymentDate(),
                isSettled: $entity->isSettled(),
                settledAt: $entity->getSettledAt(),
                status: $entity->getStatus(),
                detailInfo: $entity->getDetailInfo(),
                lockVersion: LockVersion::create(),
            );

            $this->syncMongoDB(
                entity: $newEntity,
                schemaClass: MdbEmployeeDebtSchema::class
            );

            return $newEntity;
        });
    }

    public function update(EmployeeDebt $entity): EmployeeDebt
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
                schemaClass: MdbEmployeeDebtSchema::class
            );
            
            return $entity;
        });
    }

    public function delete(EmployeeDebt $entity): EmployeeDebt
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
                schemaClass: MdbEmployeeDebtSchema::class
            );

            return $deletedEntity;
        });
    }

    public function restore(int $id): ?EmployeeDebt
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

        $entity = EmployeeDebt::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            employeeId: (int) $row['employee_id'],
            deductionTypeId: (int) $row['deduction_type_id'],
            amount: (float) $row['amount'],
            date: $row['date'],
            note: $row['note'],
            paymentTerm: (int) $row['payment_term'],
            paymentMethod: $row['payment_method'],
            installmentCount: (int) $row['installment_count'],
            installmentAmount: (float) $row['installment_amount'],
            remainingAmount: (float) $row['remaining_amount'],
            paidAmount: (float) $row['paid_amount'],
            firstPaymentDate: $row['first_payment_date'],
            isSettled: (int) $row['is_settled'],
            settledAt: $row['settled_at'],
            status: ResourceStatus::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()])
        );

        $entity->restore();

        return $this->update(
            entity: $entity,
        );
    }

    private function mapEntityToTable(EmployeeDebt $entity, int $lockVersion): array
    {
        return [
            'name' => $entity->getName(),
            'employee_id' => $entity->getEmployeeId(),
            'deduction_type_id' => $entity->getDeductionTypeId(),
            'amount' => $entity->getAmount(),
            'date' => $entity->getDate(),
            'note' => $entity->getnote(),
            'payment_term' => $entity->getPaymentTerm(),
            'payment_method' => $entity->getPaymentMethod(),
            'installment_count' => $entity->getInstallmentCount(),
            'installment_amount' => $entity->getInstallmentAmount(),
            'remaining_amount' => $entity->getRemainingAmount(),
            'paid_amount' => $entity->getPaidAmount(),
            'first_payment_date' => $entity->getFirstPaymentDate(),
            'is_settled' => $entity->isSettled(),
            'settled_at' => $entity->getSettledAt(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            SyncMdb::field() => $entity->getSyncMdbValue(),
            LockVersion::field() => $lockVersion,
        ];
    }
}
