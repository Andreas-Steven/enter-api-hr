<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\WorkPatternRule;

// Domain Layer
use App\Domain\WorkPatternRule\Entity\WorkPatternRule;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbWorkPatternRuleSchema
{
    public static function toArray(WorkPatternRule $entity): array
    {
        return [
            'name' => $entity->getName(),
            'rule_type' => $entity->getRuleType()->value,
            'target_count' => $entity->getTargetCount(),
            'holiday_wins' => $entity->getHolidayWins(),
            'holiday_type' => $entity->getHolidayType()?->value,
            'substitution_allowed' => $entity->getSubstitutionAllowed(),
            'excess_treatment' => $entity->getExcessTreatment()->value,
            'deficit_treatment' => $entity->getDeficitTreatment()->value,
            'notes' => $entity->getNotes(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}