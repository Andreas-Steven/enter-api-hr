<?php

declare(strict_types=1);

namespace App\Api\V1\EmployeeDebt\Validation;

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
use Yiisoft\Validator\Rule\Number;
use Yiisoft\Validator\Rule\StopOnError;
use Yiisoft\Validator\Rule\Callback;

/**
 * EmployeeDebt Input Validator
 * 
 * Menggunakan pattern AbstractValidator dengan ValidationContext
 * untuk validasi input yang berbeda per operation
 */
final class EmployeeDebtInputValidator extends AbstractValidator
{
    /**
     * EmployeeDebts for validation rules
     * 
     * This validator shows employeedebts of advanced validation rules including
     * dependency checking and unique value validation.
     * 
     * @see HasNoDependencies
     * @see UniqueValue
     * 
     * Usage employeedebt for HasNoDependencies:
     * ```php
     * ValidationContext::DELETE => [
     *     'id' => [
     *         new StopOnError([
     *             new Required(),
     *             new Integer(min: 1),
     *             new HasNoDependencies(
     *                 map: [
     *                     'other_table' => ['employee_debt_id'],
     *                 ],
     *                 message: 'Data tidak bisa dihapus karena masih digunakan di tabel lain.'
     *             ),
     *         ]),
     *     ],
     * ],
     * ```
     * 
     * Usage employeedebt for UniqueValue:
     * ```php
     * ValidationContext::CREATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'employeedebts',
     *                 column: 'name',
     *                 ignoreId: null,
     *             ),
     *         ]),
     *     ],
     * ],
     * 
     * Usage employeedebt for UniqueValue with exclusion (for updates):
     * ```php
     * ValidationContext::UPDATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'employeedebts',
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
                // 'name' => [
                //     new StopOnError([
                //         new Required(),
                //         new StringValue(),
                //         new Length(
                //             min: 3,
                //             max: 255,
                //         ),
                //         new UniqueValue(
                //             table: 'employee_debt',
                //             column: 'name',
                //             ignoreId: null
                //         )
                //     ])
                // ],
                'employee_id' => [
                    new Required(),
                    new Integer(
                        min: 1,
                    ),
                ],
                'deduction_type_id' => [
                    new Required(),
                    new Integer(
                        min: 1,
                    ),
                ],
                'amount' => [
                    new Required(),
                    new Number(
                        min: 0,
                    ),
                ],
                'date' => [
                    new Required(),
                    new StringValue(),
                ],
                'note' => [
                    new StringValue(),
                    new Length(
                        max: 255,
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
                // 'name' => [
                //     new StopOnError([
                //         new StringValue(
                //             skipOnEmpty: true,
                //         ),
                //         new Length(
                //             min: 3,
                //             max: 255,
                //             skipOnEmpty: true,
                //         ),
                //         new UniqueValue(
                //             table: 'employee_debt',
                //             column: 'name',
                //             ignoreId: $this->data['id'] ?? null
                //         )
                //     ])
                // ],
                'employee_id' => [
                    new Integer(
                        skipOnEmpty: true,
                        min: 1,
                    ),
                ],
                'deduction_type_id' => [
                    new Integer(
                        skipOnEmpty: true,
                        min: 1,
                    ),
                ],
                'amount' => [
                    new Number(
                        skipOnEmpty: true,
                        min: 1,
                    ),
                ],
                'date' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                ],
                'note' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                    new Length(
                        max: 255,
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
                    // new Callback(
                    //     skipOnEmpty: true,
                    //     callback: function ($value) {
                    //         if (is_int($value)) {
                    //             return (string) $value;
                    //         }
                    //         return $value;
                    //     },
                    // ),
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
                        skipOnEmpty: true,
                    ),
                ],
                'deduction_type_id' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                ],
                'date' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                ],
                'note' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                    new Length(
                        max: 255,
                        skipOnEmpty: true,
                    ),
                ],
                'is_settled' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                    new In(
                        [0, 1],
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
