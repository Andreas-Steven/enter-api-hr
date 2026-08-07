<?php

declare(strict_types=1);

namespace App\Api\V1\PayrollDeduction\Validation;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Validation\AbstractValidator;
use App\Shared\Context\ValidationContext;

// Vendor Layer
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\StringValue;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Number;

/**
 * PayrollDeduction Input Validator
 *
 * Menggunakan pattern AbstractValidator dengan ValidationContext
 * untuk validasi input yang berbeda per operation
 */
final class PayrollDeductionInputValidator extends AbstractValidator
{
    protected function rules(string $context): array
    {
        return match ($context) {
            ValidationContext::CREATE => [
                'employee_id' => [
                    new Required(),
                    new Integer(min: 1),
                ],
                'deduction_type_id' => [
                    new Required(),
                    new Integer(min: 1),
                ],
                'period' => [
                    new Required(),
                    new Integer(min: 190001, max: 210012),
                ],
                'amount' => [
                    new Required(),
                    new Number(min: 0),
                ],
                'description' => [
                    new StringValue(),
                    new Length(max: 255),
                ],
                'status' => [
                    new Required(),
                    new Integer(),
                    new In(RecordStatus::draftOnlyStates()),
                ],
            ],
            ValidationContext::UPDATE => [
                'id' => [
                    new Required(),
                    new Integer(min: 1),
                ],
                'employee_id' => [
                    new Integer(skipOnEmpty: true, min: 1),
                ],
                'deduction_type_id' => [
                    new Integer(skipOnEmpty: true, min: 1),
                ],
                'period' => [
                    new Integer(skipOnEmpty: true, min: 190001, max: 210012),
                ],
                'amount' => [
                    new Number(skipOnEmpty: true, min: 0),
                ],
                'description' => [
                    new StringValue(skipOnEmpty: true),
                    new Length(max: 255, skipOnEmpty: true),
                ],
                'status' => [
                    new Integer(skipOnEmpty: true),
                    new In(RecordStatus::searchableStates()),
                ],
                'lock_version' => [
                    new Required(when: fn() => $this->shouldValidateOptimisticLock()),
                    new Integer(min: 1, skipOnEmpty: true),
                ],
            ],
            ValidationContext::DELETE => [
                'id' => [
                    new Required(),
                    new Integer(min: 1),
                ],
            ],
            ValidationContext::PAYROLL_DEDUCTION_DEBT_PAYMENT => [
                'employee_debt_id' => [
                    new Required(),
                    new Integer(min: 1),
                ],
                'period' => [
                    new Required(),
                    new Integer(min: 190001, max: 210012),
                ],
                'amount' => [
                    new Required(),
                    new Number(min: 0),
                ],
                'description' => [
                    new StringValue(),
                    new Length(max: 255),
                ],
                'status' => [
                    new Required(),
                    new Integer(),
                    new In(RecordStatus::draftOnlyStates()),
                ],
            ],
            ValidationContext::SEARCH => [
                'id' => [
                    new Integer(skipOnEmpty: true),
                ],
                'name' => [
                    new StringValue(skipOnEmpty: true),
                    new Length(min: 1, max: 100, skipOnEmpty: true),
                ],
                'employee_id' => [
                    new Integer(skipOnEmpty: true),
                ],
                'deduction_type_id' => [
                    new Integer(skipOnEmpty: true),
                ],
                'period' => [
                    new Integer(skipOnEmpty: true, min: 190001, max: 210012),
                ],
                'status' => [
                    new Integer(skipOnEmpty: true),
                    new In(RecordStatus::searchableStates()),
                ],
            ],
            default => [],
        };
    }
}
