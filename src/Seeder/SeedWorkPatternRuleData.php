<?php

declare(strict_types=1);

namespace App\Seeder;

use App\Domain\WorkPatternRule\Entity\WorkPatternRule;
use App\Domain\WorkPatternRule\Repository\WorkPatternRuleRepositoryInterface;
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Application\Shared\Factory\DetailInfoFactory;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds WorkPatternRule table using Alice fixtures.
 */
final class SeedWorkPatternRuleData extends AbstractSeederData
{
    private WorkPatternRuleRepositoryInterface $repository;

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
        $entity->updateDetailInfo($detailInfo);
        $this->repository->insert($entity);
    }
}