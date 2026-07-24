<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\AttendanceReport;

// Domain Layer
use App\Domain\AttendanceReport\Entity\AttendanceReport;
use App\Domain\AttendanceReport\Repository\AttendanceReportRepositoryInterface;
use App\Domain\AttendanceReport\ValueObject\Summary;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;

// Infrastructure Layer
use App\Infrastructure\Concerns\HasCoreFeatures;
use App\Infrastructure\Concerns\HasMongoDBSync;
use App\Infrastructure\Concerns\ManagesPersistence;
use App\Infrastructure\Database\MongoDB\MongoDBService;
use App\Infrastructure\Persistence\AttendanceReport\MdbAttendanceReportSchema;
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
 * AttendanceReport Repository using Yiisoft/Db Query Builder
 * 
 * Pure query implementation using Yiisoft/Db for database operations
 */
final class AttendanceReportRepository implements AttendanceReportRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures, HasMongoDBSync, ManagesPersistence;

    public const TABLE_NAME = 'attendance_report';
    public const SEQUENCE_ID = 'attendance_report_id_seq';
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
        return AttendanceReport::RESOURCE;
    }

    public function findById(int $id, ?int $status = null): ?AttendanceReport
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

    public function findByName(string $name, ?int $status = null): ?AttendanceReport
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

    public function findByPeriodAndEmployeeId(int $period, int $employeeId, ?int $status = null): ?AttendanceReport
    {
        /** @var array<string, mixed>|false $row */
        $row = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where([
                'period' => $period,
                'employee_id' => $employeeId,
            ])
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

    public function list(SearchCriteria $criteria): PaginatedResult
    {
        $query = (new Query($this->db))
            ->select([
                'id',
                'name',
                'period',
                'employee_id',
                'branch_id',
                'division_id',
                'position_id',
                'employee_type_id',
                'work_pattern_id',
                'summary',
                'report_type',
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
                'employee_id',
                'branch_id',
                'division_id',
                'position_id',
                'employee_type_id',
                'work_pattern_id',
                'report_type',
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
                jsonKeys: ['summary']
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

    public function insert(AttendanceReport $entity): AttendanceReport 
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

            $newEntity = AttendanceReport::reconstitute(
                id: $newId,
                name: $entity->getName(),
                period: $entity->getPeriod(),
                employeeId: $entity->getEmployeeId(),
                branchId: $entity->getBranchId(),
                divisionId: $entity->getDivisionId(),
                positionId: $entity->getPositionId(),
                employeeTypeId: $entity->getEmployeeTypeId(),
                workPatternId: $entity->getWorkPatternId(),
                summary: $entity->getSummary(),
                reportType: $entity->getReportType(),
                status: $entity->getStatus(),
                detailInfo: $entity->getDetailInfo(),
                syncMdb: $entity->getSyncMdb(),
                lockVersion: LockVersion::create(),
            );

            $this->syncMongoDB(
                entity: $newEntity,
                schemaClass: MdbAttendanceReportSchema::class
            );

            return $newEntity;
        });
    }

    public function update(AttendanceReport $entity): AttendanceReport
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
                schemaClass: MdbAttendanceReportSchema::class
            );
            
            return $entity;
        });
    }

    public function delete(AttendanceReport $entity): AttendanceReport
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
                schemaClass: MdbAttendanceReportSchema::class
            );

            return $deletedEntity;
        });
    }

    public function restore(int $id): ?AttendanceReport
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
     * @param array<string, mixed> $row
     */
    private function reconstituteFromRow(array $row): AttendanceReport
    {
        return AttendanceReport::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            period: (int) $row['period'],
            employeeId: (int) $row['employee_id'],
            branchId: (int) $row['branch_id'],
            divisionId: (int) $row['division_id'],
            positionId: (int) $row['position_id'],
            employeeTypeId: (int) $row['employee_type_id'],
            workPatternId: (int) $row['work_pattern_id'],
            summary: Summary::fromArray($this->castToArray($row['summary'] ?? null)),
            reportType: $row['report_type'] ?? 'HEK',
            status: ResourceStatus::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            syncMdb: SyncMdb::fromInt($row[SyncMdb::field()] ?? null),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
        );
    }

    private function mapEntityToTable(AttendanceReport $entity, int $lockVersion): array
    {
        return [
            'name' => $entity->getName(),
            'period' => $entity->getPeriod(),
            'employee_id' => $entity->getEmployeeId(),
            'branch_id' => $entity->getBranchId(),
            'division_id' => $entity->getDivisionId(),
            'position_id' => $entity->getPositionId(),
            'employee_type_id' => $entity->getEmployeeTypeId(),
            'work_pattern_id' => $entity->getWorkPatternId(),
            'summary' => $entity->getSummary()->toArray(),
            'report_type' => $entity->getReportType(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            SyncMdb::field() => $entity->getSyncMdbValue(),
            LockVersion::field() => $lockVersion,
        ];
    }
}
