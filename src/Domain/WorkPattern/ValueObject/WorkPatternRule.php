<?php

declare(strict_types=1);

namespace App\Domain\WorkPattern\ValueObject;

use App\Domain\Shared\Enums\DeficitTreatment;
use App\Domain\Shared\Enums\ExcessTreatment;
use App\Domain\Shared\Enums\HolidayType;
use App\Domain\Shared\Enums\WorkPatternRuleType;
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

/**
 * Value object representing a work pattern rule attached to a working day.
 *
 * The rule is optional per working day (nullable). When present, it describes
 * how attendance for that day should be evaluated against a target.
 */
final readonly class WorkPatternRule
{
    public function __construct(
        private ?int $id,
        private string $name,
        private WorkPatternRuleType $ruleType,
        private int $targetCount,
        private bool $holidayWins,
        private ?HolidayType $holidayType,
        private bool $substitutionAllowed,
        private ExcessTreatment $excessTreatment,
        private DeficitTreatment $deficitTreatment,
        private ?string $notes,
    ) {
        if ($targetCount < 0) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'work_pattern.invalid_target_count',
                    params: [],
                    domain: 'app'
                )
            );
        }

        if ($holidayWins && $holidayType === null) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'work_pattern.holiday_type_required',
                    params: [],
                    domain: 'app'
                )
            );
        }
    }

    public static function fromArray(?array $data): ?self
    {
        if ($data === null) {
            return null;
        }

        $ruleType = self::resolveRuleType($data['rule_type'] ?? null);
        $excessTreatment = self::resolveExcessTreatment($data['excess_treatment'] ?? null);
        $deficitTreatment = self::resolveDeficitTreatment($data['deficit_treatment'] ?? null);
        $holidayType = self::resolveHolidayType($data['holiday_type'] ?? null);

        $id = isset($data['id']) && is_numeric($data['id']) ? (int) $data['id'] : null;
        $name = is_string($data['name'] ?? null) ? trim($data['name']) : '';

        if ($name === '') {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'required',
                    params: ['attribute' => 'work_pattern_rule.name'],
                    domain: 'validation'
                )
            );
        }

        return new self(
            id: $id,
            name: $name,
            ruleType: $ruleType,
            targetCount: (int) ($data['target_count'] ?? 0),
            holidayWins: self::resolveBool($data['holiday_wins'] ?? false),
            holidayType: $holidayType,
            substitutionAllowed: self::resolveBool($data['substitution_allowed'] ?? false),
            excessTreatment: $excessTreatment,
            deficitTreatment: $deficitTreatment,
            notes: isset($data['notes']) && is_string($data['notes']) ? $data['notes'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'rule_type' => $this->ruleType->value,
            'target_count' => $this->targetCount,
            'holiday_wins' => $this->holidayWins ? 1 : 0,
            'holiday_type' => $this->holidayType?->value,
            'substitution_allowed' => $this->substitutionAllowed ? 1 : 0,
            'excess_treatment' => $this->excessTreatment->value,
            'deficit_treatment' => $this->deficitTreatment->value,
            'notes' => $this->notes,
        ];
    }

    private static function resolveRuleType(mixed $value): WorkPatternRuleType
    {
        if (!is_string($value) || $value === '') {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'required',
                    params: ['attribute' => 'work_pattern_rule.rule_type'],
                    domain: 'validation'
                )
            );
        }

        return WorkPatternRuleType::tryFrom($value)
            ?? throw new BadRequestException(
                translate: Message::create(
                    key: 'work_pattern.invalid_rule_type',
                    params: ['value' => $value],
                    domain: 'app'
                )
            );
    }

    private static function resolveExcessTreatment(mixed $value): ExcessTreatment
    {
        if (!is_string($value) || $value === '') {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'required',
                    params: ['attribute' => 'work_pattern_rule.excess_treatment'],
                    domain: 'validation'
                )
            );
        }

        return ExcessTreatment::tryFrom($value)
            ?? throw new BadRequestException(
                translate: Message::create(
                    key: 'work_pattern.invalid_excess_treatment',
                    params: ['value' => $value],
                    domain: 'app'
                )
            );
    }

    private static function resolveDeficitTreatment(mixed $value): DeficitTreatment
    {
        if (!is_string($value) || $value === '') {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'required',
                    params: ['attribute' => 'work_pattern_rule.deficit_treatment'],
                    domain: 'validation'
                )
            );
        }

        return DeficitTreatment::tryFrom($value)
            ?? throw new BadRequestException(
                translate: Message::create(
                    key: 'work_pattern.invalid_deficit_treatment',
                    params: ['value' => $value],
                    domain: 'app'
                )
            );
    }

    private static function resolveHolidayType(mixed $value): ?HolidayType
    {
        if ($value === null || $value === '' || !is_string($value)) {
            return null;
        }

        return HolidayType::tryFrom($value)
            ?? throw new BadRequestException(
                translate: Message::create(
                    key: 'work_pattern.invalid_holiday_type',
                    params: ['value' => $value],
                    domain: 'app'
                )
            );
    }

    private static function resolveBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return false;
    }
}
