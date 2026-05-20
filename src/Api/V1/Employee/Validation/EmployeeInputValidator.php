<?php

declare(strict_types=1);

namespace App\Api\V1\Employee\Validation;

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
use Yiisoft\Validator\Rule\Date\Date;

/**
 * Employee Input Validator
 * 
 * Menggunakan pattern AbstractValidator dengan ValidationContext
 * untuk validasi input yang berbeda per operation
 */
final class EmployeeInputValidator extends AbstractValidator
{
    /**
     * Employees for validation rules
     * 
     * This validator shows employees of advanced validation rules including
     * dependency checking and unique value validation.
     * 
     * @see HasNoDependencies
     * @see UniqueValue
     * 
     * Usage employee for HasNoDependencies:
     * ```php
     * ValidationContext::DELETE => [
     *     'id' => [
     *         new StopOnError([
     *             new Required(),
     *             new Integer(min: 1),
     *             new HasNoDependencies(
     *                 map: [
     *                     'other_table' => ['employee_id'],
     *                 ],
     *                 message: 'Data tidak bisa dihapus karena masih digunakan di tabel lain.'
     *             ),
     *         ]),
     *     ],
     * ],
     * ```
     * 
     * Usage employee for UniqueValue:
     * ```php
     * ValidationContext::CREATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'employees',
     *                 column: 'name',
     *                 ignoreId: null,
     *             ),
     *         ]),
     *     ],
     * ],
     * 
     * Usage employee for UniqueValue with exclusion (for updates):
     * ```php
     * ValidationContext::UPDATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'employees',
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
                            table: 'employee', 
                            column: 'name', 
                            ignoreId: null
                        )
                    ])
                ],
                'username' => [
                    new StopOnError([
                        new Required(),
                        new StringValue(),
                        new Length(
                            min: 3, 
                            max: 255,
                        ),
                        new UniqueValue(
                            table: 'employee', 
                            column: 'username', 
                            ignoreId: null
                        )
                    ])
                ],
                'sso_id' => [
                    new StopOnError([
                        new Required(),
                        new Integer(),
                        new UniqueValue(
                            table: 'employee', 
                            column: 'sso_id', 
                            ignoreId: null
                        )
                    ])
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
                            table: 'employee', 
                            column: 'name', 
                            ignoreId: $this->data['id'] ?? null
                        )
                    ])
                ],
                'division_id' => [
                    new Integer(
                        skipOnEmpty: true,
                        min: 1,
                    ),
                ],
                'position_id' => [
                    new Integer(
                        skipOnEmpty: true,
                        min: 1,
                    ),
                ],
                'work_time_id' => [
                    new Integer(
                        skipOnEmpty: true,
                        min: 1,
                    ),
                ],
                'employee_type_id' => [
                    new Integer(
                        skipOnEmpty: true,
                        min: 1,
                    ),
                ],
                'branch_id' => [
                    new Integer(
                        skipOnEmpty: true,
                        min: 1,
                    ),
                ],
                'email' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                    new Length(
                        max: 255,
                        skipOnEmpty: true,
                    ),
                ],
                'nik' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                    new Length(
                        max: 50,
                        skipOnEmpty: true,
                    ),
                ],
                'birth_date' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                ],
                'date_start' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                ],
                'date_resign' => [
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
                'sso_id' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                ],
                'nik' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                    new Length(
                        max: 50,
                        skipOnEmpty: true,
                    ),
                ],
                'division_id' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                ],
                'branch_id' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                ],
                'birth_date' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                ],
                'date_start' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                ],
                'date_resign' => [
                    new StringValue(
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
