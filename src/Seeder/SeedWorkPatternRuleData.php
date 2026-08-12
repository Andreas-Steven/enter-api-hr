<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\WorkPatternRule\Entity\WorkPatternRule;
// use App\Infrastructure\Persistence\WorkPatternRule\WorkPatternRuleRepository;
use App\Domain\WorkPatternRule\Repository\WorkPatternRuleRepositoryInterface;

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
 * Seeds workpatternrule table using Alice fixtures.
 */
final class SeedWorkPatternRuleData extends AbstractSeederData
{
    private WorkPatternRuleRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'workpatternrule.yaml';
    protected const ENTITY_CLASS = WorkPatternRule::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        WorkPatternRuleRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = WorkPatternRule::create(
            $entity->getName(),
            $entity->getStatus(),
            $detailInfo
        );
        
        // Use repository to insert
        $this->repository->insert($newEntity);   
    }
}
