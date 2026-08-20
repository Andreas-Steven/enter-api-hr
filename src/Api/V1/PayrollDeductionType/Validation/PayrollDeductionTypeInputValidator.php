<?php

declare(strict_types=1);

namespace App\Api\V1\PayrollDeductionType\Validation;

// Domain Layer
use App\Domain\Shared\Enums\CreatesDebt;
use App\Domain\Shared\Enums\DeductionType as DeductionTypeEnum;

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
 * PayrollDeductionType Input Validator
 * 
 * Menggunakan pattern AbstractValidator dengan ValidationContext
 * untuk validasi input yang berbeda per operation
 */
final class PayrollDeductionTypeInputValidator extends AbstractValidator
{
    /**
     * PayrollDeductionTypes for validation rules
     * 
     * This validator shows deductiontypes of advanced validation rules including
     * dependency checking and unique value validation.
     * 
     * @see HasNoDependencies
     * @see UniqueValue
     * 
     * Usage deductiontype for HasNoDependencies:
     * ```php
     * ValidationContext::DELETE => [
     *     'id' => [
     *         new StopOnError([
     *             new Required(),
     *             new Integer(min: 1),
     *             new HasNoDependencies(
     *                 map: [
     *                     'other_table' => ['deduction_type_id'],
     *                 ],
     *                 message: 'Data tidak bisa dihapus karena masih digunakan di tabel lain.'
     *             ),
     *         ]),
     *     ],
     * ],
     * ```
     * 
     * Usage deductiontype for UniqueValue:
     * ```php
     * ValidationContext::CREATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'deductiontypes',
     *                 column: 'name',
     *                 ignoreId: null,
     *             ),
     *         ]),
     *     ],
     * ],
     * 
     * Usage deductiontype for UniqueValue with exclusion (for updates):
     * ```php
     * ValidationContext::UPDATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'deductiontypes',
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
                            table: 'payroll_deduction_type', 
                            column: 'name', 
                            ignoreId: null
                        )
                    ])
                ],
                'category' => [
                    new StopOnError([
                        new Required(),
                        new StringValue(),
                        new Length(
                            min: 3,
                            max: 255,
                        ),
                        new In(
                            DeductionTypeEnum::values(),
                        ),
                    ])
                ],
                'creates_debt' => [
                    new Required(),
                    new Integer(),
                    new In(
                        CreatesDebt::values(),
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
                            table: 'payroll_deduction_type', 
                            column: 'name', 
                            ignoreId: $this->data['id'] ?? null
                        )
                    ])
                ],
                'category' => [
                    new StopOnError([
                        new StringValue(
                            skipOnEmpty: true,
                        ),
                        new Length(
                            min: 3,
                            max: 255,
                            skipOnEmpty: true,
                        ),
                        new In(
                            DeductionTypeEnum::values(),
                            skipOnEmpty: true,
                        ),
                    ])
                ],
                'creates_debt' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                    new In(
                        CreatesDebt::values(),
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
                'category' => [
                    new StopOnError([
                        new StringValue(
                            skipOnEmpty: true,
                        ),
                        new Length(
                            min: 1,
                            max: 100,
                            skipOnEmpty: true,
                        ),
                        new In(
                            DeductionTypeEnum::values(),
                            skipOnEmpty: true,
                        ),
                    ])
                ],
                'creates_debt' => [
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
