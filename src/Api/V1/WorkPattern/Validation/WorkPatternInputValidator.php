<?php

declare(strict_types=1);

namespace App\Api\V1\WorkPattern\Validation;

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
use Yiisoft\Validator\Rule\Each;
use Yiisoft\Validator\Rule\Nested;


/**
 * WorkPattern Input Validator
 *
 * Menggunakan pattern AbstractValidator dengan ValidationContext
 * untuk validasi input yang berbeda per operation
 */
final class WorkPatternInputValidator extends AbstractValidator
{
    /**
     * WorkPatterns for validation rules
     *
     * This validator shows workpatterns of advanced validation rules including
     * dependency checking and unique value validation.
     *
     * @see HasNoDependencies
     * @see UniqueValue
     *
     * Usage workpattern for HasNoDependencies:
     * ```php
     * ValidationContext::DELETE => [
     *     'id' => [
     *         new StopOnError([
     *             new Required(),
     *             new Integer(min: 1),
     *             new HasNoDependencies(
     *                 map: [
     *                     'other_table' => ['work_pattern_id'],
     *                 ],
     *                 message: 'Data tidak bisa dihapus karena masih digunakan di tabel lain.'
     *             ),
     *         ]),
     *     ],
     * ],
     * ```
     *
     * Usage workpattern for UniqueValue:
     * ```php
     * ValidationContext::CREATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'work_patterns',
     *                 column: 'name',
     *                 ignoreId: null,
     *             ),
     *         ]),
     *     ],
     * ],
     *
     * Usage workpattern for UniqueValue with exclusion (for updates):
     * ```php
     * ValidationContext::UPDATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'work_patterns',
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
                            table: 'work_pattern',
                            column: 'name',
                            ignoreId: null
                        )
                    ])
                ],
                'working_days' => [
                    new Required(),
                    new Each([
                        new Nested([
                            'day_of_week' => [
                                new Required(),
                                new Integer(min: 1, max: 7),
                            ],
                            'shift_id' => [
                                new Integer(
                                    skipOnEmpty: true,
                                    min: 1,
                                ),
                            ],
                            'work_pattern_rule_id' => [
                                new Integer(
                                    skipOnEmpty: true,
                                    min: 1,
                                ),
                            ],
                        ]),
                    ]),
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
                            table: 'work_pattern',
                            column: 'name',
                            ignoreId: $this->data['id'] ?? null
                        )
                    ])
                ],
                'working_days' => [
                    new Each([
                        new Nested([
                            'day_of_week' => [
                                new Integer(
                                    min: 1,
                                    max: 7,
                                    skipOnEmpty: true,
                                ),
                            ],
                            'shift_id' => [
                                new Integer(
                                    skipOnEmpty: true,
                                    min: 1,
                                ),
                            ],
                            'work_pattern_rule_id' => [
                                new Integer(
                                    skipOnEmpty: true,
                                    min: 1,
                                ),
                            ],
                        ]),
                    ], skipOnEmpty: $this->data['id'] !== null),
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
