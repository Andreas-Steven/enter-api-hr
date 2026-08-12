<?php

declare(strict_types=1);

namespace App\Application\WorkPatternRule\Command;

// Domain Layer
use App\Domain\Shared\Enums\DeficitTreatment;
use App\Domain\Shared\Enums\ExcessTreatment;
use App\Domain\Shared\Enums\HolidayType;
use App\Domain\Shared\Enums\WorkPatternRuleType;

final readonly class CreateWorkPatternRuleCommand
{
    public function __construct(
        public string $name,
        public WorkPatternRuleType $ruleType,
        public int $targetCount,
        public int $holidayWins,
        public ?HolidayType $holidayType,
        public int $substitutionAllowed,
        public ExcessTreatment $excessTreatment,
        public DeficitTreatment $deficitTreatment,
        public ?string $notes,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        string $name,
        WorkPatternRuleType $ruleType,
        int $targetCount,
        int $holidayWins,
        ?HolidayType $holidayType,
        int $substitutionAllowed,
        ExcessTreatment $excessTreatment,
        DeficitTreatment $deficitTreatment,
        ?string $notes,
        int $status,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            ruleType: $ruleType,
            targetCount: $targetCount,
            holidayWins: $holidayWins,
            holidayType: $holidayType,
            substitutionAllowed: $substitutionAllowed,
            excessTreatment: $excessTreatment,
            deficitTreatment: $deficitTreatment,
            notes: $notes,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}
