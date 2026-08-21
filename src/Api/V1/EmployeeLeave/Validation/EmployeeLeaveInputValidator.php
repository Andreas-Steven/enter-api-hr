<?php

declare(strict_types=1);

namespace App\Api\V1\EmployeeLeave\Validation;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Validation\AbstractValidator;
use App\Shared\Context\ValidationContext;
use App\Shared\Validation\Rules\HasNoDependencies;
use App\Shared\Validation\Rules\UniqueValue;

// Domain Layer
use App\Domain\Shared\Enums\AdjustmentType;
use App\Domain\Shared\Enums\AdjustmentReason;

// Vendor Layer
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\StringValue;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\StopOnError;

/**
 * EmployeeLeave Input Validator
 * 
 * Menggunakan pattern AbstractValidator dengan ValidationContext
 * untuk validasi input yang berbeda per operation
 */
final class EmployeeLeaveInputValidator extends AbstractValidator
{
    /**
     * EmployeeLeaves for validation rules
     * 
     * This validator shows employeeleaves of advanced validation rules including
     * dependency checking and unique value validation.
     * 
     * @see HasNoDependencies
     * @see UniqueValue
     * 
     * Usage employeeleave for HasNoDependencies:
     * ```php
     * ValidationContext::DELETE => [
     *     'id' => [
     *         new StopOnError([
     *             new Required(),
     *             new Integer(min: 1),
     *             new HasNoDependencies(
     *                 map: [
     *                     'other_table' => ['employee_leave_id'],
     *                 ],
     *                 message: 'Data tidak bisa dihapus karena masih digunakan di tabel lain.'
     *             ),
     *         ]),
     *     ],
     * ],
     * ```
     * 
     * Usage employeeleave for UniqueValue:
     * ```php
     * ValidationContext::CREATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'employeeleaves',
     *                 column: 'name',
     *                 ignoreId: null,
     *             ),
     *         ]),
     *     ],
     * ],
     * 
     * Usage employeeleave for UniqueValue with exclusion (for updates):
     * ```php
     * ValidationContext::UPDATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'employeeleaves',
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
                    ])
                ],
                'leave_type' => [
                    new StopOnError([
                        new Required(),
                        new StringValue(),
                        new Length(
                            min: 1,
                            max: 100,
                        ),
                    ])
                ],
                'employee_id' => [
                    new Required(),
                    new Integer(
                        min: 1,
                    ),
                ],
                'period_start_date' => [
                    new StopOnError([
                        new Required(),
                        new StringValue(),
                    ])
                ],
                'period_end_date' => [
                    new StopOnError([
                        new Required(),
                        new StringValue(),
                    ])
                ],
                'initial_amount' => [
                    new Required(),
                    new Integer(
                        min: 0,
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
            ValidationContext::ASSIGN => [
                'name' => [
                    new StopOnError([
                        new Required(),
                        new StringValue(),
                        new Length(
                            min: 3,
                            max: 255,
                        ),
                    ])
                ],
                'leave_type' => [
                    new StopOnError([
                        new Required(),
                        new StringValue(),
                        new Length(
                            min: 1,
                            max: 100,
                        ),
                    ])
                ],
                'period_start_date' => [
                    new StopOnError([
                        new Required(),
                        new StringValue(),
                    ])
                ],
                'period_end_date' => [
                    new StopOnError([
                        new Required(),
                        new StringValue(),
                    ])
                ],
                'initial_amount' => [
                    new Required(),
                    new Integer(
                        min: 0,
                    ),
                ],
                'status' => [
                    new Required(),
                    new Integer(),
                    new In(
                        RecordStatus::searchableStates(),
                    ),
                ],
                'scope' => [
                    new Required(),
                    new \Yiisoft\Validator\Rule\Nested(
                        rules: [
                            'branch_id' => new Integer(min: 1, skipOnEmpty: true),
                            'division_id' => new Integer(min: 1, skipOnEmpty: true),
                            'employee_type_id' => new Integer(min: 1, skipOnEmpty: true),
                            'employee_ids' => new \Yiisoft\Validator\Rule\Each(
                                rules: [new Integer(min: 1)],
                                skipOnEmpty: true,
                            ),
                        ],
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
                        // new UniqueValue(
                        //     table: 'employee_leave', 
                        //     column: 'name', 
                        //     ignoreId: $this->data['id'] ?? null
                        // )
                    ])
                ],
                'leave_type' => [
                    new StopOnError([
                        new StringValue(
                            skipOnEmpty: true,
                        ),
                        new Length(
                            min: 1,
                            max: 100,
                            skipOnEmpty: true,
                        ),
                    ])
                ],
                'employee_id' => [
                    new Integer(
                        min: 1,
                        skipOnEmpty: true,
                    ),
                ],
                'period_start_date' => [
                    new StopOnError([
                        new StringValue(
                            skipOnEmpty: true,
                        ),
                    ])
                ],
                'period_end_date' => [
                    new StopOnError([
                        new StringValue(
                            skipOnEmpty: true,
                        ),
                    ])
                ],
                'initial_amount' => [
                    new Integer(
                        min: 0,
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
            ValidationContext::ADJUST => [
                'id' => [
                    new Required(),
                    new Integer(
                        min: 1,
                    ),
                ],
                'adjustment_amount' => [
                    new Required(),
                    new Integer(
                        min: 1,
                    ),
                ],
                'adjustment_type' => [
                    new Required(),
                    new StringValue(),
                    new In(
                        AdjustmentType::values(),
                    ),
                ],
                'adjustment_leave_type' => [
                    new Required(),
                    new StringValue(),
                    new In(
                        AdjustmentReason::values(),
                    ),
                ],
                'adjustment_note' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                    new Length(
                        max: 500,
                        skipOnEmpty: true,
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
                'leave_type' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                    new Length(
                        min: 1,
                        max: 100,
                        skipOnEmpty: true,
                    ),
                ],
                'employee_id' => [
                    new Integer(
                        min: 1,
                        skipOnEmpty: true,
                    ),
                ],
                'period_start_date' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                ],
                'period_end_date' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                ],
                'initial_amount' => [
                    new Integer(
                        min: 0,
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

