<?php

declare(strict_types=1);

namespace App\Api\V1\WorkPatternRule\Validation;

// Domain Layer
use App\Domain\Shared\Enums\DeficitTreatment;
use App\Domain\Shared\Enums\ExcessTreatment;
use App\Domain\Shared\Enums\HolidayType;
use App\Domain\Shared\Enums\WorkPatternRuleType;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Validation\AbstractValidator;
use App\Shared\Context\ValidationContext;
use App\Shared\Validation\Rules\HasNoDependencies;
use App\Shared\Validation\Rules\UniqueValue;

// Vendor Layer
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\StringValue;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\StopOnError;

/**
 * WorkPatternRule Input Validator
 * 
 * Menggunakan pattern AbstractValidator dengan ValidationContext
 * untuk validasi input yang berbeda per operation
 */
final class WorkPatternRuleInputValidator extends AbstractValidator
{
    /**
     * WorkPatternRules for validation rules
     * 
     * This validator shows workpatternrules of advanced validation rules including
     * dependency checking and unique value validation.
     * 
     * @see HasNoDependencies
     * @see UniqueValue
     * 
     * Usage workpatternrule for HasNoDependencies:
     * ```php
     * ValidationContext::DELETE => [
     *     'id' => [
     *         new StopOnError([
     *             new Required(),
     *             new Integer(min: 1),
     *             new HasNoDependencies(
     *                 map: [
     *                     'other_table' => ['work_pattern_rules_id'],
     *                 ],
     *                 message: 'Data tidak bisa dihapus karena masih digunakan di tabel lain.'
     *             ),
     *         ]),
     *     ],
     * ],
     * ```
     * 
     * Usage workpatternrule for UniqueValue:
     * ```php
     * ValidationContext::CREATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'workpatternrules',
     *                 column: 'name',
     *                 ignoreId: null,
     *             ),
     *         ]),
     *     ],
     * ],
     * 
     * Usage workpatternrule for UniqueValue with exclusion (for updates):
     * ```php
     * ValidationContext::UPDATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'workpatternrules',
     *                 column: 'name',
     *                 ignoreId: $this->data['id'] ?? null,
     *             ),
     *         ]),
     *     ],
     * ],
     * ```
     */
    

    protected function rules(string $context): array
    {
        return match ($context) {
            ValidationContext::CREATE => [
                'name' => [
                    new StopOnError([
                        new Required(),
                        new StringValue(),
                        new Length(
                            min: 3,
                            max: 255,
                        ),
                        new UniqueValue(
                            table: 'work_pattern_rules',
                            column: 'name',
                            ignoreId: null
                        )
                    ])
                ],
                'rule_type' => [
                    new Required(),
                    new In(WorkPatternRuleType::values()),
                ],
                'target_count' => [
                    new Required(),
                    new Integer(min: 0),
                ],
                'holiday_wins' => [
                    new Required(),
                    new In([0, 1]),
                ],
                'holiday_type' => [
                    new In(
                        HolidayType::values(),
                        skipOnEmpty: true,
                    ),
                ],
                'substitution_allowed' => [
                    new Required(),
                    new In([0, 1]),
                ],
                'excess_treatment' => [
                    new Required(),
                    new In(ExcessTreatment::values()),
                ],
                'deficit_treatment' => [
                    new Required(),
                    new In(DeficitTreatment::values()),
                ],
                'notes' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                ],
                'status' => [
                    new Required(),
                    new Integer(),
                    new In(
                        RecordStatus::draftOnlyStates(),
                    ),
                ],
            ],
            ValidationContext::UPDATE => [
                'id' => [
                    new Required(),
                    new Integer(
                        min: 1,
                    ),
                ],
                'name' => [
                    new StopOnError([
                        new StringValue(
                            skipOnEmpty: true,
                        ),
                        new Length(
                            min: 3,
                            max: 255,
                            skipOnEmpty: true,
                        ),
                        new UniqueValue(
                            table: 'work_pattern_rules',
                            column: 'name',
                            ignoreId: $this->data['id'] ?? null
                        )
                    ])
                ],
                'rule_type' => [
                    new In(
                        WorkPatternRuleType::values(),
                        skipOnEmpty: true,
                    ),
                ],
                'target_count' => [
                    new Integer(
                        min: 0,
                        skipOnEmpty: true,
                    ),
                ],
                'holiday_wins' => [
                    new In(
                        [0, 1],
                        skipOnEmpty: true,
                    ),
                ],
                'holiday_type' => [
                    new In(
                        HolidayType::values(),
                        skipOnEmpty: true,
                    ),
                ],
                'substitution_allowed' => [
                    new In(
                        [0, 1],
                        skipOnEmpty: true,
                    ),
                ],
                'excess_treatment' => [
                    new In(
                        ExcessTreatment::values(),
                        skipOnEmpty: true,
                    ),
                ],
                'deficit_treatment' => [
                    new In(
                        DeficitTreatment::values(),
                        skipOnEmpty: true,
                    ),
                ],
                'notes' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                ],
                'status' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                    new In(
                        RecordStatus::searchableStates(),
                    ),
                ],
                'lock_version' => [
                    new Required(
                        when: fn() => $this->shouldValidateOptimisticLock()
                    ),
                    new Integer(
                        min: 1,
                        skipOnEmpty: true,
                    ),
                ],
            ],
            ValidationContext::DELETE => [
                'id' => [
                    new Required(), 
                    new Integer(
                        min: 1,
                    ),
                ],
            ],
            ValidationContext::SEARCH => [
                'id' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                ],
                'name' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                    new Length(
                        min: 1,
                        max: 100,
                        skipOnEmpty: true,
                    ),
                ],
                'rule_type' => [
                    new In(
                        WorkPatternRuleType::values(),
                        skipOnEmpty: true,
                    ),
                ],
                'holiday_wins' => [
                    new In(
                        [0, 1],
                        skipOnEmpty: true,
                    ),
                ],
                'holiday_type' => [
                    new In(
                        HolidayType::values(),
                        skipOnEmpty: true,
                    ),
                ],
                'substitution_allowed' => [
                    new In(
                        [0, 1],
                        skipOnEmpty: true,
                    ),
                ],
                'excess_treatment' => [
                    new In(
                        ExcessTreatment::values(),
                        skipOnEmpty: true,
                    ),
                ],
                'deficit_treatment' => [
                    new In(
                        DeficitTreatment::values(),
                        skipOnEmpty: true,
                    ),
                ],
                'status' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                ],
                'sync_mdb' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                ],
                'page' => [
                    new Integer(
                        min: 1,
                        skipOnEmpty: true,
                    ),
                ],
                'page_size' => [
                    new Integer(
                        min: 1,
                        max: 200,
                        skipOnEmpty: true,
                    ),
                ],
                'sort_by' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                ],
                'sort_dir' => [
                    new In(
                        ['asc', 'desc'],
                        skipOnEmpty: true,
                    ),
                ],
            ],

            default => [],
        };
    }
}
