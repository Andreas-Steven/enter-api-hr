<?php

declare(strict_types=1);

namespace App\Api\V1\EmployeeGrade\Validation;

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
 * EmployeeGrade Input Validator
 * 
 * Menggunakan pattern AbstractValidator dengan ValidationContext
 * untuk validasi input yang berbeda per operation
 */
final class EmployeeGradeInputValidator extends AbstractValidator
{
    /**
     * EmployeeGrades for validation rules
     * 
     * This validator shows employeegrades of advanced validation rules including
     * dependency checking and unique value validation.
     * 
     * @see HasNoDependencies
     * @see UniqueValue
     * 
     * Usage employeegrade for HasNoDependencies:
     * ```php
     * ValidationContext::DELETE => [
     *     'id' => [
     *         new StopOnError([
     *             new Required(),
     *             new Integer(min: 1),
     *             new HasNoDependencies(
     *                 map: [
     *                     'other_table' => ['employee_grade_id'],
     *                 ],
     *                 message: 'Data tidak bisa dihapus karena masih digunakan di tabel lain.'
     *             ),
     *         ]),
     *     ],
     * ],
     * ```
     * 
     * Usage employeegrade for UniqueValue:
     * ```php
     * ValidationContext::CREATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'employeegrades',
     *                 column: 'name',
     *                 ignoreId: null,
     *             ),
     *         ]),
     *     ],
     * ],
     * 
     * Usage employeegrade for UniqueValue with exclusion (for updates):
     * ```php
     * ValidationContext::UPDATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'employeegrades',
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
                            table: 'employee_grade', 
                            column: 'name', 
                            ignoreId: null
                        )
                    ])
                ],
                'level' => [
                    new Required(),
                    new Integer(
                        min: 1,
                    ),
                    new UniqueValue(
                        table: 'employee_grade', 
                        column: 'level', 
                        ignoreId: null
                    )
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
                            table: 'employee_grade', 
                            column: 'name', 
                            ignoreId: $this->data['id'] ?? null
                        )
                    ])
                ],
                'level' => [
                    new Integer(
                        min: 1,
                        skipOnEmpty: true,
                    ),
                    new UniqueValue(
                        table: 'employee_grade', 
                        column: 'level', 
                        ignoreId: $this->data['id'] ?? null
                    )
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
                ],
                'level' => [
                    new Integer(
                        min: 1,
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
