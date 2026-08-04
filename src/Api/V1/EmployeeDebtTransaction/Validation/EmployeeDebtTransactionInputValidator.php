<?php

declare(strict_types=1);

namespace App\Api\V1\EmployeeDebtTransaction\Validation;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Domain\Shared\Enums\TransactionType;
use App\Domain\Shared\Enums\PaymentSource;
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
 * EmployeeDebtTransaction Input Validator
 *
 * Menggunakan pattern AbstractValidator dengan ValidationContext
 * untuk validasi input yang berbeda per operation
 */
final class EmployeeDebtTransactionInputValidator extends AbstractValidator
{
    protected function rules(string $context): array
    {
        return match ($context) {
            ValidationContext::CREATE => [
                'employee_debt_id' => [
                    new Required(),
                    new Integer(min: 1),
                ],
                'employee_id' => [
                    new Required(),
                    new Integer(min: 1),
                ],
                'transaction_type' => [
                    new Required(),
                    new StringValue(),
                    new In(TransactionType::ALL),
                ],
                'date' => [
                    new Required(),
                    new StringValue(),
                    new Length(exactly: 10),
                ],
                'amount' => [
                    new Required(),
                    new Number(),
                ],
                'payment_source' => [
                    new StringValue(),
                    new In(PaymentSource::ALL),
                ],
                'note' => [
                    new StringValue(),
                    new Length(max: 1000),
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
                'employee_debt_id' => [
                    new Integer(skipOnEmpty: true, min: 1),
                ],
                'employee_id' => [
                    new Integer(skipOnEmpty: true, min: 1),
                ],
                'transaction_type' => [
                    new StringValue(
                        skipOnEmpty: true
                    ),
                    new In(TransactionType::ALL),
                ],
                'date' => [
                    new StringValue(
                        skipOnEmpty: true
                    ),
                ],
                'amount' => [
                    new Number(skipOnEmpty: true),
                ],
                'payment_source' => [
                    new StringValue(
                        skipOnEmpty: true
                    ),
                    new In(PaymentSource::ALL),
                ],
                'note' => [
                    new StringValue(skipOnEmpty: true),
                    new Length(max: 1000, skipOnEmpty: true),
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
            ValidationContext::SEARCH => [
                'id' => [
                    new Integer(skipOnEmpty: true),
                ],
                'name' => [
                    new StringValue(skipOnEmpty: true),
                    new Length(min: 1, max: 100, skipOnEmpty: true),
                ],
                'employee_debt_id' => [
                    new Integer(skipOnEmpty: true),
                ],
                'employee_id' => [
                    new Integer(skipOnEmpty: true),
                ],
                'transaction_type' => [
                    new StringValue(skipOnEmpty: true),
                    new In(TransactionType::ALL),
                ],
                'payment_source' => [
                    new StringValue(skipOnEmpty: true),
                    new In(PaymentSource::ALL),
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
