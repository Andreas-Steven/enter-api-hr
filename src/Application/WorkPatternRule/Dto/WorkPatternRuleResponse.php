<?php

declare(strict_types=1);

namespace App\Application\WorkPatternRule\Dto;

use App\Domain\Shared\Enums\DeficitTreatment;
use App\Domain\Shared\Enums\ExcessTreatment;
use App\Domain\Shared\Enums\WorkPatternRuleType;
use App\Domain\WorkPatternRule\Entity\WorkPatternRule;

final readonly class WorkPatternRuleResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $rule_type,
        public ?int $target_count,
        public int $holiday_wins,
        /** @var list<string>|null */
        public ?array $holiday_type,
        public int $substitution_allowed,
        public string $excess_treatment,
        public string $deficit_treatment,
        public ?string $notes,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(WorkPatternRule $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            rule_type: $entity->getRuleType()->value,
            target_count: $entity->getTargetCount(),
            holiday_wins: $entity->getHolidayWins(),
            holiday_type: $entity->getHolidayTypeValues(),
            substitution_allowed: $entity->getSubstitutionAllowed(),
            excess_treatment: $entity->getExcessTreatment()->value,
            deficit_treatment: $entity->getDeficitTreatment()->value,
            notes: $entity->getNotes(),
            status: $entity->getStatus()->value(),
            detail_info: $entity->getDetailInfo()->toArray(),
            sync_mdb: $entity->getSyncMdbValue(),
            lock_version: $entity->getLockVersion()->value(),
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
