<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\AttendanceReview;

// Domain Layer
use App\Domain\AttendanceReview\Entity\AttendanceReview;
use App\Domain\AttendanceReview\Repository\AttendanceReviewRepositoryInterface;
use App\Domain\Shared\Enums\ApprovalStatus;
use App\Domain\Shared\Enums\ReviewReason;
use App\Domain\Shared\Enums\ReviewSourceType;
use App\Domain\AttendanceReview\ValueObject\SourceRef;
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
use App\Shared\ValueObject\Message;

// Vendor Layer
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * AttendanceReview Repository using Yiisoft/Db Query Builder
 */
final class AttendanceReviewRepository implements AttendanceReviewRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures, HasMongoDBSync, ManagesPersistence;

    public const TABLE_NAME = 'attendance_review';
    public const SEQUENCE_ID = 'attendance_review_id_seq';
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
        return AttendanceReview::RESOURCE;
    }

    public function findById(int $id, ?int $status = null): ?AttendanceReview
    {
        $row = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['id' => $id])
            ->andWhere($this->scopeWhereNotDeleted())
            ->andWhere($this->scopeByStatus($status))
            ->one();

        if (!$row) {
            return null;
        }

        return $this->hydrateEntity($row);
    }

    public function findByName(string $name, ?int $status = null): ?AttendanceReview
    {
        $row = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['name' => $name])
            ->andWhere($this->scopeWhereNotDeleted())
            ->andWhere($this->scopeByStatus($status))
            ->one();

        if (!$row) {
            return null;
        }

        return $this->hydrateEntity($row);
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

    public function findByStableKey(int $employeeId, int $period, string $stableKey): ?AttendanceReview
    {
        $row = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where([
                'employee_id' => $employeeId,
                'period' => $period,
                'stable_key' => $stableKey,
            ])
            ->andWhere($this->scopeWhereNotDeleted())
            ->one();

        if (!$row) {
            return null;
        }

        return $this->hydrateEntity($row);
    }

    public function existsByStableKey(int $employeeId, int $period, string $stableKey): bool
    {
        return (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where([
                'employee_id' => $employeeId,
                'period' => $period,
                'stable_key' => $stableKey,
            ])
            ->andWhere($this->scopeWhereNotDeleted())
            ->exists();
    }

    public function findByEmployeeAndPeriod(int $employeeId, int $period, ?ApprovalStatus $approvalStatus = null): array
    {
        $query = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where([
                'employee_id' => $employeeId,
                'period' => $period,
            ])
            ->andWhere($this->scopeWhereNotDeleted());

        if ($approvalStatus !== null) {
            $query->andWhere(['approval_status' => $approvalStatus->value]);
        }

        $rows = $query->all();

        $entities = [];
        foreach ($rows as $row) {
            $entities[] = $this->hydrateEntity($row);
        }

        return $entities;
    }

    public function findByAttendanceSummaryId(int $attendanceSummaryId, ?ApprovalStatus $approvalStatus = null): array
    {
        $query = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['attendance_summary_id' => $attendanceSummaryId])
            ->andWhere($this->scopeWhereNotDeleted());

        if ($approvalStatus !== null) {
            $query->andWhere(['approval_status' => $approvalStatus->value]);
        }

        $rows = $query->all();

        $entities = [];
        foreach ($rows as $row) {
            $entities[] = $this->hydrateEntity($row);
        }

        return $entities;
    }

    public function countPendingByEmployeeAndPeriod(int $employeeId, int $period): int
    {
        return (int) (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where([
                'employee_id' => $employeeId,
                'period' => $period,
                'approval_status' => ApprovalStatus::PENDING->value,
            ])
            ->andWhere($this->scopeWhereNotDeleted())
            ->count();
    }

    public function list(SearchCriteria $criteria): PaginatedResult
    {
        $query = (new Query($this->db))
            ->select([
                'id',
                'name',
                'employee_id',
                'period',
                'attendance_summary_id',
                'source_type',
                'source_ref',
                'stable_key',
                'review_reason',
                'source_treatment',
                'resolved_treatment',
                'approval_status',
                'approved_by',
                'approved_note',
                'approved_at',
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
                'period',
                'attendance_summary_id',
                'source_type',
                'review_reason',
                'approval_status',
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
                jsonKeys: ['source_ref', 'detail_info']
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

    public function insert(AttendanceReview $entity): AttendanceReview
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

            $newEntity = AttendanceReview::reconstitute(
                id: $newId,
                name: $entity->getName(),
                employeeId: $entity->getEmployeeId(),
                period: $entity->getPeriod(),
                attendanceSummaryId: $entity->getAttendanceSummaryId(),
                sourceType: $entity->getSourceType(),
                sourceRef: $entity->getSourceRef(),
                stableKey: $entity->getStableKey(),
                reviewReason: $entity->getReviewReason(),
                sourceTreatment: $entity->getSourceTreatment(),
                resolvedTreatment: $entity->getResolvedTreatment(),
                approvalStatus: $entity->getApprovalStatus(),
                approvedBy: $entity->getApprovedBy(),
                approvedNote: $entity->getApprovedNote(),
                approvedAt: $entity->getApprovedAt(),
                status: $entity->getStatus(),
                detailInfo: $entity->getDetailInfo(),
                lockVersion: LockVersion::create(),
            );

            $this->syncMongoDB(
                entity: $newEntity,
                schemaClass: MdbAttendanceReviewSchema::class
            );

            return $newEntity;
        });
    }

    public function update(AttendanceReview $entity): AttendanceReview
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
                schemaClass: MdbAttendanceReviewSchema::class
            );

            return $entity;
        });
    }

    public function delete(AttendanceReview $entity): AttendanceReview
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
                schemaClass: MdbAttendanceReviewSchema::class
            );

            return $deletedEntity;
        });
    }

    public function restore(int $id): ?AttendanceReview
    {
        $row = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['id' => $id])
            ->andWhere($this->scopeWhereDeleted())
            ->one();

        if (!$row) {
            return null;
        }

        $entity = $this->hydrateEntity($row);
        $entity->restore();

        return $this->update(
            entity: $entity,
        );
    }

    private function hydrateEntity(array $row): AttendanceReview
    {
        $sourceRefData = $this->parseJsonColumn($row, 'source_ref');

        return AttendanceReview::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            employeeId: (int) $row['employee_id'],
            period: (int) $row['period'],
            attendanceSummaryId: $row['attendance_summary_id'] !== null ? (int) $row['attendance_summary_id'] : null,
            sourceType: ReviewSourceType::from((string) $row['source_type']),
            sourceRef: SourceRef::fromArray($sourceRefData),
            stableKey: (string) $row['stable_key'],
            reviewReason: ReviewReason::from((string) $row['review_reason']),
            sourceTreatment: (string) $row['source_treatment'],
            resolvedTreatment: $row['resolved_treatment'] !== null ? (string) $row['resolved_treatment'] : null,
            approvalStatus: ApprovalStatus::from((int) $row['approval_status']),
            approvedBy: $row['approved_by'] !== null ? (int) $row['approved_by'] : null,
            approvedNote: $row['approved_note'],
            approvedAt: $row['approved_at'],
            status: ResourceStatus::from((int) $row['status']),
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

    private function mapEntityToTable(AttendanceReview $entity, int $lockVersion): array
    {
        return [
            'name' => $entity->getName(),
            'employee_id' => $entity->getEmployeeId(),
            'period' => $entity->getPeriod(),
            'attendance_summary_id' => $entity->getAttendanceSummaryId(),
            'source_type' => $entity->getSourceType()->value,
            'source_ref' => json_encode($entity->getSourceRef()->toArray(), JSON_UNESCAPED_UNICODE),
            'stable_key' => $entity->getStableKey(),
            'review_reason' => $entity->getReviewReason()->value,
            'source_treatment' => $entity->getSourceTreatment(),
            'resolved_treatment' => $entity->getResolvedTreatment(),
            'approval_status' => $entity->getApprovalStatus()->value,
            'approved_by' => $entity->getApprovedBy(),
            'approved_note' => $entity->getApprovedNote(),
            'approved_at' => $entity->getApprovedAt(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            SyncMdb::field() => $entity->getSyncMdbValue(),
            LockVersion::field() => $lockVersion,
        ];
    }
}
