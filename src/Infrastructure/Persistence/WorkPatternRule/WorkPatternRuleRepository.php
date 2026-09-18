<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\WorkPatternRule;

// Domain Layer
use App\Domain\WorkPatternRule\Entity\WorkPatternRule;
use App\Domain\WorkPatternRule\Repository\WorkPatternRuleRepositoryInterface;
use App\Domain\Shared\Enums\DeficitTreatment;
use App\Domain\Shared\Enums\ExcessTreatment;
use App\Domain\Shared\Enums\HolidayType;
use App\Domain\Shared\Enums\WorkPatternRuleType;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;

// Infrastructure Layer
use App\Infrastructure\Concerns\HasCoreFeatures;
use App\Infrastructure\Concerns\HasMongoDBSync;
use App\Infrastructure\Concerns\ManagesPersistence;
use App\Infrastructure\Database\MongoDB\MongoDBService;
use App\Infrastructure\Persistence\WorkPatternRule\MdbWorkPatternRuleSchema;
use App\Infrastructure\Security\CurrentUserAwareInterface;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Query\QueryConditionApplier;
use App\Shared\ValueObject\Message;

// Vendor Layer
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\QueryBuilder\Condition\JsonOverlaps;

// use MongoDB\Collection;

/**
 * WorkPatternRule Repository using Yiisoft/Db Query Builder
 * 
 * Pure query implementation using Yiisoft/Db for database operations
 */
final class WorkPatternRuleRepository implements WorkPatternRuleRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures, HasMongoDBSync, ManagesPersistence;

    public const TABLE_NAME = 'work_pattern_rules';
    public const SEQUENCE_ID = 'work_pattern_rules_id_seq';
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
        return WorkPatternRule::RESOURCE;
    }

    public function findById(int $id, ?int $status = null): ?WorkPatternRule
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

    public function findByName(string $name, ?int $status = null): ?WorkPatternRule
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

        return $this->hydrateEntity($row)->updateSyncMdb(SyncMdb::fromInt($row[SyncMdb::field()] ?? null));
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
                'rule_type',
                'target_count',
                'holiday_wins',
                'holiday_type',
                'substitution_allowed',
                'excess_treatment',
                'deficit_treatment',
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
                'rule_type',
                'holiday_wins',
                'substitution_allowed',
                'excess_treatment',
                'deficit_treatment',
                'status',
                SyncMdb::field(),
            ]
        );

        if (!empty($filter['holiday_type'])) {
            $query->andWhere(new JsonOverlaps(
                column: 'holiday_type',
                values: $filter['holiday_type'],
            ));
        }

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

        $rows = array_map(
            function (array $row): array {
                $row['holiday_type'] = $this->decodeHolidayTypeValues($row['holiday_type'] ?? null);
                return $row;
            },
            iterator_to_array(
                $this->streamRows(
                    query: $query,
                    jsonKeys: [],
                )
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
                'dir' => $criteria->sortDir
            ]
        );
    }

    public function insert(WorkPatternRule $entity): WorkPatternRule 
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

            $newEntity = WorkPatternRule::reconstitute(
                id: $newId,
                name: $entity->getName(),
                ruleType: $entity->getRuleType(),
                targetCount: $entity->getTargetCount(),
                holidayWins: $entity->getHolidayWins(),
                holidayTypes: $entity->getHolidayTypes(),
                substitutionAllowed: $entity->getSubstitutionAllowed(),
                excessTreatment: $entity->getExcessTreatment(),
                deficitTreatment: $entity->getDeficitTreatment(),
                notes: $entity->getNotes(),
                status: $entity->getStatus(),
                detailInfo: $entity->getDetailInfo(),
                lockVersion: LockVersion::create(),
            );

            $this->syncMongoDB(
                entity: $newEntity,
                schemaClass: MdbWorkPatternRuleSchema::class
            );

            return $newEntity;
        });
    }

    public function update(WorkPatternRule $entity): WorkPatternRule
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
                schemaClass: MdbWorkPatternRuleSchema::class
            );
            
            return $entity;
        });
    }

    public function delete(WorkPatternRule $entity): WorkPatternRule
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
                schemaClass: MdbWorkPatternRuleSchema::class
            );

            return $deletedEntity;
        });
    }

    public function restore(int $id): ?WorkPatternRule
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

        $entity = $this->hydrateEntity($row);

        $entity->restore();

        return $this->update(
            entity: $entity,
        );
    }

    private function mapEntityToTable(WorkPatternRule $entity, int $lockVersion): array
    {
        return [
            'name' => $entity->getName(),
            'rule_type' => $entity->getRuleType()->value,
            'target_count' => $entity->getTargetCount(),
            'holiday_wins' => $entity->getHolidayWins(),
            'holiday_type' => $this->serializeHolidayTypes(
                holidayTypes: $entity->getHolidayTypeValues(),
            ),
            'substitution_allowed' => $entity->getSubstitutionAllowed(),
            'excess_treatment' => $entity->getExcessTreatment()->value,
            'deficit_treatment' => $entity->getDeficitTreatment()->value,
            'notes' => $entity->getNotes(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            SyncMdb::field() => $entity->getSyncMdbValue(),
            LockVersion::field() => $lockVersion,
        ];
    }

    /**
     * @return list<string>|null
     */
    private function decodeHolidayTypeValues(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = json_last_error() === JSON_ERROR_NONE
                ? (is_array($decoded) ? $decoded : [$decoded])
                : [$value];
        }

        if (!is_array($value)) {
            return null;
        }

        return array_values(array_map(
            static fn(mixed $holidayType): string => (string) $holidayType,
            $value,
        ));
    }

    /**
     * @return list<HolidayType>|null
     */
    private function hydrateHolidayTypes(mixed $value): ?array
    {
        $holidayTypeValues = $this->decodeHolidayTypeValues($value);

        if ($holidayTypeValues === null) {
            return null;
        }

        return array_map(
            static fn(string $holidayType): HolidayType => HolidayType::from($holidayType),
            $holidayTypeValues,
        );
    }

    private function serializeHolidayTypes(?array $holidayTypes): ?string
    {
        return $holidayTypes === null
            ? null
            : json_encode($holidayTypes, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateEntity(array $row): WorkPatternRule
    {
        $holidayTypes = $this->hydrateHolidayTypes($row['holiday_type'] ?? null);

        return WorkPatternRule::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            ruleType: WorkPatternRuleType::from((string) $row['rule_type']),
            targetCount: $row['target_count'] !== null
                ? (int) $row['target_count']
                : null,
            holidayWins: (int) $row['holiday_wins'],
            holidayTypes: $holidayTypes,
            substitutionAllowed: (int) $row['substitution_allowed'],
            excessTreatment: ExcessTreatment::from((string) $row['excess_treatment']),
            deficitTreatment: DeficitTreatment::from((string) $row['deficit_treatment']),
            notes: $row['notes'] ?? null,
            status: ResourceStatus::from((int) $row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
        );
    }
}
