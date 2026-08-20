<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\PayrollReport\Entity\PayrollReport;
use App\Domain\PayrollReport\Repository\PayrollReportRepositoryInterface;
use App\Domain\PayrollReport\ValueObject\AttendanceSummaryRef;
use App\Domain\PayrollReport\ValueObject\EmployeeSnapshot;
use App\Domain\PayrollReport\ValueObject\PayrollSummary;

// PSR Interfaces
use Psr\Clock\ClockInterface;

// Vendor Layer
use Yiisoft\Db\Connection\ConnectionInterface;

// Application Layer
use App\Application\Shared\Factory\DetailInfoFactory;
use App\Shared\Query\QueryConditionApplier;

// Infrastructure Layer
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Infrastructure\Database\MongoDB\MongoDBService;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds payrollreport table using Alice fixtures.
 */
final class SeedPayrollReportData extends AbstractSeederData
{
    private PayrollReportRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'payrollreport.yaml';
    protected const ENTITY_CLASS = PayrollReport::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        PayrollReportRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = PayrollReport::create(
            name: $entity->getName(),
            period: $entity->getPeriod(),
            employee: $entity->getEmployee(),
            attendanceSummary: $entity->getAttendanceSummary(),
            payrollSummary: $entity->getPayrollSummary(),
            overtimeSummary: $entity->getOvertimeSummary(),
            payrollNet: $entity->getPayrollNet(),
            status: $entity->getStatus(),
            detailInfo: $detailInfo,
        );

        // Use repository to insert
        $this->repository->insert($newEntity);
    }
}
