<?php

declare(strict_types=1);

namespace App\Application\WorkPatternRule\Command;

// Domain Layer
use App\Domain\Shared\Enums\DeficitTreatment;
use App\Domain\Shared\Enums\ExcessTreatment;
use App\Domain\Shared\Enums\HolidayType;
use App\Domain\Shared\Enums\WorkPatternRuleType;

final readonly class UpdateWorkPatternRuleCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?WorkPatternRuleType $ruleType,
        public ?int $targetCount,
        public ?int $holidayWins,
        public ?HolidayType $holidayType,
        public ?int $substitutionAllowed,
        public ?ExcessTreatment $excessTreatment,
        public ?DeficitTreatment $deficitTreatment,
        public ?string $notes,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
        public bool $targetCountProvided = false,
    ) {}

    public static function create(
        int $id,
        ?string $name = null,
        ?WorkPatternRuleType $ruleType = null,
        ?int $targetCount = null,
        ?int $holidayWins = null,
        ?HolidayType $holidayType = null,
        ?int $substitutionAllowed = null,
        ?ExcessTreatment $excessTreatment = null,
        ?DeficitTreatment $deficitTreatment = null,
        ?string $notes = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
        bool $targetCountProvided = false,
    ): self {
        return new self(
            id: $id,
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
            lockVersion: $lockVersion,
            targetCountProvided: $targetCountProvided,
        );
    }
}
