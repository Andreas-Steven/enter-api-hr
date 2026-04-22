<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Employee;

// Domain Layer
use App\Domain\Employee\Entity\Employee;
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;
use App\Domain\Employee\ValueObject\Contact;
use App\Domain\Employee\ValueObject\Image;
use App\Domain\Employee\ValueObject\BirthDate;
use App\Domain\Employee\ValueObject\DateStart;
use App\Domain\Employee\ValueObject\DateResign;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;

// Infrastructure Layer
use App\Infrastructure\Concerns\HasCoreFeatures;
use App\Infrastructure\Concerns\HasMongoDBSync;
use App\Infrastructure\Concerns\ManagesPersistence;
use App\Infrastructure\Database\MongoDB\MongoDBService;
use App\Infrastructure\Persistence\Employee\MdbEmployeeSchema;
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
 * Employee Repository using Yiisoft/Db Query Builder
 * 
 * Pure query implementation using Yiisoft/Db for database operations
 */
final class EmployeeRepository implements EmployeeRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures, HasMongoDBSync, ManagesPersistence;

    public const TABLE_NAME = 'employee';
    public const SEQUENCE_ID = 'employee_id_seq';
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
        return Employee::RESOURCE;
    }

    public function findById(int $id, ?int $status = null): ?Employee
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

        return Employee::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            username: $row['username'],
            nik: $row['nik'],
            email: $row['email'],
            contact: Contact::fromArray($this->castToArray($row['contact'])),
            birthDate: $row['birth_date'],
            dateStart: $row['date_start'],
            dateResign: $row['date_resign'],
            ssoId: (int) $row['sso_id'],
            divisionId: (int) $row['division_id'],
            positionId: (int) $row['position_id'],
            employeeTypeId: (int) $row['employee_type_id'],
            workTimeId: (int) $row['work_time_id'],
            image: Image::fromArray($this->castToArray($row['image'])),
            status: ResourceStatus::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
        );
    }

    /**
     * @param int[] $ids
     * @return array<int, Employee>
     */
    public function findAllByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        /** @var array<int, array<string, mixed>> $rows */
        $rows = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['id' => $ids])
            ->andWhere($this->scopeWhereNotDeleted())
            ->all();

        $employees = [];
        foreach ($rows as $row) {
            $employee = Employee::reconstitute(
                id: (int) $row['id'],
                name: $row['name'],
                username: $row['username'],
                nik: $row['nik'],
                email: $row['email'],
                contact: Contact::fromArray($this->castToArray($row['contact'])),
                birthDate: $row['birth_date'],
                dateStart: $row['date_start'],
                dateResign: $row['date_resign'],
                ssoId: (int) $row['sso_id'],
                divisionId: (int) $row['division_id'],
                positionId: (int) $row['position_id'],
                employeeTypeId: (int) $row['employee_type_id'],
                workTimeId: (int) $row['work_time_id'],
                image: Image::fromArray($this->castToArray($row['image'])),
                status: ResourceStatus::from((int)$row['status']),
                detailInfo: DetailInfo::fromJson($row['detail_info']),
                lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
            );

            // Penting: Indexing berdasarkan ID agar Domain Service bisa akses instan $employees[$id]
            $employees[$employee->getId()] = $employee;
        }

        return $employees;
    }

    public function findByName(string $name, ?int $status = null): ?Employee
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

        return Employee::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            username: $row['username'],
            nik: $row['nik'],
            email: $row['email'],
            contact: Contact::fromArray($this->castToArray($row['contact'])),
            birthDate: $row['birth_date'] ? BirthDate::fromString($row['birth_date']) : null,
            dateStart: $row['date_start'] ? DateStart::fromString($row['date_start']) : null,
            dateResign: $row['date_resign'] ? DateResign::fromString($row['date_resign']) : null,
            ssoId: (int) $row['sso_id'],
            divisionId: (int) $row['division_id'],
            positionId: (int) $row['position_id'],
            employeeTypeId: (int) $row['employee_type_id'],
            workTimeId: (int) $row['work_time_id'],
            image: Image::fromArray($this->castToArray($row['image'])),
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
                'username',
                'nik',
                'email',
                'contact',
                'birth_date',
                'date_start',
                'date_resign',
                'sso_id',
                'division_id',
                'position_id',
                'employee_type_id',
                'work_time_id',
                'image',
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
                'status', 
                SyncMdb::field(),
            ]
        );

        if (!empty($filter['name'])) {
            $this->queryConditionApplier->orLike(
                query: $query, 
                operator: self::LIKE_OPERATOR,
                conditions: ['name' => $filter['name']]
            );
        }

        $total = (clone $query)->count();

        $query->orderBy($criteria->getOrderClause())
            ->limit($criteria->pageSize)
            ->offset($criteria->calculateOffset());

        $rows = iterator_to_array(
            $this->streamRows(
                query: $query, 
                jsonKeys: [
                    'contact',
                    'image',
                ]
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

    public function insert(Employee $entity): Employee 
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

            $newEntity = Employee::reconstitute(
                id: $newId,
                name: $entity->getName(),
                username: $entity->getUsername(),
                ssoId: $entity->getSsoId(),
                nik: $entity->getNik(),
                email: $entity->getEmail(),
                contact: $entity->getContact(),
                birthDate: $entity->getBirthDate(),
                dateStart: $entity->getDateStart(),
                dateResign: $entity->getDateResign(),
                divisionId: $entity->getDivisionId(),
                positionId: $entity->getPositionId(),
                employeeTypeId: $entity->getEmployeeTypeId(),
                workTimeId: $entity->getWorkTimeId(),
                image: $entity->getImage(),
                status: $entity->getStatus(),
                detailInfo: $entity->getDetailInfo(),
                lockVersion: LockVersion::create(),
            );

            $this->syncMongoDB(
                entity: $newEntity,
                schemaClass: MdbEmployeeSchema::class
            );

            return $newEntity;
        });
    }

    public function update(Employee $entity): Employee
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
                schemaClass: MdbEmployeeSchema::class
            );
            
            return $entity;
        });
    }

    public function delete(Employee $entity): Employee
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
                schemaClass: MdbEmployeeSchema::class
            );

            return $deletedEntity;
        });
    }

    public function restore(int $id): ?Employee
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

        $entity = Employee::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            username: $row['username'],
            ssoId: (int) $row['sso_id'],
            nik: $row['nik'],
            email: $row['email'],
            contact: Contact::fromArray($this->castToArray($row['contact'])),
            birthDate: $row['birth_date'] ? BirthDate::fromString($row['birth_date']) : null,
            dateStart: $row['date_start'] ? DateStart::fromString($row['date_start']) : null,
            dateResign: $row['date_resign'] ? DateResign::fromString($row['date_resign']) : null,
            divisionId: (int) $row['division_id'],
            positionId: (int) $row['position_id'],
            employeeTypeId: (int) $row['employee_type_id'],
            workTimeId: (int) $row['work_time_id'],
            image: Image::fromArray($this->castToArray($row['image'])),
            status: ResourceStatus::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()])
        );

        $entity->restore();

        return $this->update(
            entity: $entity,
        );
    }

    private function mapEntityToTable(Employee $entity, int $lockVersion): array
    {
        return [
            'name' => $entity->getName(),
            'username' => $entity->getUsername(),
            'nik' => $entity->getNik(),
            'email' => $entity->getEmail(),
            'contact' => $entity->getContact(),
            'birth_date' => $entity->getBirthDate(),
            'date_start' => $entity->getDateStart(),
            'date_resign' => $entity->getDateResign(),
            'sso_id' => $entity->getSsoId(),
            'division_id' => $entity->getDivisionId(),
            'position_id' => $entity->getPositionId(),
            'employee_type_id' => $entity->getEmployeeTypeId(),
            'work_time_id' => $entity->getWorkTimeId(),
            'image' => $entity->getImage(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            SyncMdb::field() => $entity->getSyncMdbValue(),
            LockVersion::field() => $lockVersion,
        ];
    }
}
