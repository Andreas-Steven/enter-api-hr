<?php

declare(strict_types=1);

namespace App\Api\V1\EmployeeDebtInstallment\Validation;

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

/**
 * EmployeeDebtInstallment Input Validator
 * 
 * Menggunakan pattern AbstractValidator dengan ValidationContext
 * untuk validasi input yang berbeda per operation
 */
final class EmployeeDebtInstallmentInputValidator extends AbstractValidator
{
    /**
     * EmployeeDebtInstallments for validation rules
     * 
     * This validator shows employeedebtinstallments of advanced validation rules including
     * dependency checking and unique value validation.
     * 
     * @see HasNoDependencies
     * @see UniqueValue
     * 
     * Usage employeedebtinstallment for HasNoDependencies:
     * ```php
     * ValidationContext::DELETE => [
     *     'id' => [
     *         new StopOnError([
     *             new Required(),
     *             new Integer(min: 1),
     *             new HasNoDependencies(
     *                 map: [
     *                     'other_table' => ['employee_debt_installment_id'],
     *                 ],
     *                 message: 'Data tidak bisa dihapus karena masih digunakan di tabel lain.'
     *             ),
     *         ]),
     *     ],
     * ],
     * ```
     * 
     * Usage employeedebtinstallment for UniqueValue:
     * ```php
     * ValidationContext::CREATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'employeedebtinstallments',
     *                 column: 'name',
     *                 ignoreId: null,
     *             ),
     *         ]),
     *     ],
     * ],
     * 
     * Usage employeedebtinstallment for UniqueValue with exclusion (for updates):
     * ```php
     * ValidationContext::UPDATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'employeedebtinstallments',
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
                'employee_debt_id' => [
                    new Required(),
                    new Integer(
                        min: 1,
                    ),
                ],
                'payroll_deduction_id' => [
                    new Integer(
                        min: 1,
                    ),
                ],
                'sequence' => [
                    new Required(),
                    new Integer(
                        min: 1,
                    ),
                ],
                'due_date' => [
                    new Required(),
                    new StringValue(),
                ],
                'amount' => [
                    new Required(),
                    new Number(
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
            ValidationContext::UPDATE => [
                'id' => [
                    new Required(),
                    new Integer(
                        min: 1,
                    ),
                ],
                'employee_debt_id' => [
                    new Integer(
                        skipOnEmpty: true,
                        min: 1,
                    ),
                ],
                'payroll_deduction_id' => [
                    new Integer(
                        skipOnEmpty: true,
                        min: 1,
                    ),
                ],
                'sequence' => [
                    new Integer(
                        skipOnEmpty: true,
                        min: 1,
                    ),
                ],
                'due_date' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                ],
                'amount' => [
                    new Number(
                        skipOnEmpty: true,
                        min: 0,
                    ),
                ],
                'payment_status' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                    new In(
                        [0, 1, 2],
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
                'employee_debt_id' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                ],
                'payroll_deduction_id' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                ],
                'payment_status' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                    new In(
                        [0, 1, 2],
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
