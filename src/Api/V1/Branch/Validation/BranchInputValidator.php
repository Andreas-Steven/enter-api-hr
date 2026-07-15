<?php

declare(strict_types=1);

namespace App\Api\V1\Branch\Validation;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Validation\AbstractValidator;
use App\Shared\Context\ValidationContext;
use App\Shared\Validation\Rules\HasNoDependencies;
use App\Shared\Validation\Rules\UniqueValue;

// Domain Layer
use App\Domain\Shared\Enums\BranchType;

// Vendor Layer
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\StringValue;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\StopOnError;

/**
 * Branch Input Validator
 * 
 * Menggunakan pattern AbstractValidator dengan ValidationContext
 * untuk validasi input yang berbeda per operation
 */
final class BranchInputValidator extends AbstractValidator
{
    /**
     * Branchs for validation rules
     * 
     * This validator shows branchs of advanced validation rules including
     * dependency checking and unique value validation.
     * 
     * @see HasNoDependencies
     * @see UniqueValue
     * 
     * Usage branch for HasNoDependencies:
     * ```php
     * ValidationContext::DELETE => [
     *     'id' => [
     *         new StopOnError([
     *             new Required(),
     *             new Integer(min: 1),
     *             new HasNoDependencies(
     *                 map: [
     *                     'other_table' => ['branches_id'],
     *                 ],
     *                 message: 'Data tidak bisa dihapus karena masih digunakan di tabel lain.'
     *             ),
     *         ]),
     *     ],
     * ],
     * ```
     * 
     * Usage branch for UniqueValue:
     * ```php
     * ValidationContext::CREATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'branchs',
     *                 column: 'name',
     *                 ignoreId: null,
     *             ),
     *         ]),
     *     ],
     * ],
     * 
     * Usage branch for UniqueValue with exclusion (for updates):
     * ```php
     * ValidationContext::UPDATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'branchs',
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
                            table: 'branches', 
                            column: 'name', 
                            ignoreId: null
                        )
                    ])
                ],
                'branch_type' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                ],
                'code' => [
                    new StopOnError([
                        new Required(),
                        new StringValue(),
                        new Length(
                            min: 1, 
                            max: 255,
                        ),
                        new UniqueValue(
                            table: 'branches', 
                            column: 'code', 
                            ignoreId: null
                        )
                    ])
                ],
                'address' => [
                    new Required(),
                    new StringValue(),
                    new Length(
                        min: 1, 
                        max: 255,
                    ),
                ],
                'attendance_policies_id' => [
                    new Integer(
                        min: 1,
                        skipOnEmpty: true,
                    ),
                ],
                'work_pattern_id' => [
                    new Integer(
                        min: 1,
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
                            table: 'branches', 
                            column: 'name', 
                            ignoreId: $this->data['id'] ?? null
                        )
                    ])
                ],
                'branch_type' => [
                    new StopOnError([
                        new Integer(
                            skipOnEmpty: true,
                        ),
                        new In(
                            values: array_map(
                                static fn(BranchType $type) => $type->value,
                                BranchType::cases()
                            ),
                            skipOnEmpty: true,
                        ),
                    ])
                ],
                'code' => [
                    new StopOnError([
                        new StringValue(
                            skipOnEmpty: true,
                        ),
                        new Length(
                            min: 1, 
                            max: 255,
                            skipOnEmpty: true,
                        ),
                        new UniqueValue(
                            table: 'branches', 
                            column: 'code', 
                            ignoreId: $this->data['id'] ?? null
                        )
                    ])
                ],
                'address' => [
                    new StopOnError([
                        new StringValue(
                            skipOnEmpty: true,
                        ),
                        new Length(
                            min: 1, 
                            max: 255,
                            skipOnEmpty: true,
                        ),
                    ])
                ],
                'attendance_policies_id' => [
                    new Integer(
                        min: 1,
                        skipOnEmpty: true,
                    ),
                ],
                'work_pattern_id' => [
                    new Integer(
                        min: 1,
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
                'branch_type' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                ],
                'code' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                    new Length(
                        min: 1,
                        max: 100,
                        skipOnEmpty: true,
                    ),
                ],
                'address' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                    new Length(
                        min: 1,
                        max: 255,
                        skipOnEmpty: true,
                    ),
                ],
                'attendance_policies_id' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                ],
                'work_pattern_id' => [
                    new Integer(
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