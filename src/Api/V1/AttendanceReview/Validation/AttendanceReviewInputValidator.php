<?php

declare(strict_types=1);

namespace App\Api\V1\AttendanceReview\Validation;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Validation\AbstractValidator;
use App\Shared\Context\ValidationContext;
use App\Shared\Validation\Rules\UniqueValue;

// Domain Layer
use App\Domain\Shared\Enums\ApprovalStatus;
use App\Domain\Shared\Enums\ReviewReason;
use App\Domain\Shared\Enums\ReviewSourceType;

// Vendor Layer
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\StringValue;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\StopOnError;

/**
 * AttendanceReview Input Validator
 */
final class AttendanceReviewInputValidator extends AbstractValidator
{
    protected function rules(string $context): array
    {
        return match ($context) {
            ValidationContext::CREATE => [
                'period' => [
                    new Required(),
                    new Integer(min: 1),
                ],
                'missing_in_policy' => [
                    new StringValue(skipOnEmpty: true),
                    new Length(min: 1, max: 50, skipOnEmpty: true),
                ],
                'missing_out_policy' => [
                    new StringValue(skipOnEmpty: true),
                    new Length(min: 1, max: 50, skipOnEmpty: true),
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
                'name' => [
                    new StopOnError([
                        new StringValue(skipOnEmpty: true),
                        new Length(min: 3, max: 255, skipOnEmpty: true),
                        new UniqueValue(
                            table: 'attendance_review',
                            column: 'name',
                            ignoreId: $this->data['id'] ?? null
                        )
                    ])
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
            ValidationContext::APPROVE => [
                'id' => [
                    new Required(),
                    new Integer(min: 1),
                ],
                'decision' => [
                    new Required(),
                    new In(['APPROVE', 'APPROVED', 'REJECT', 'REJECTED']),
                ],
                'resolved_treatment' => [
                    new StringValue(skipOnEmpty: true),
                    new Length(min: 1, max: 50, skipOnEmpty: true),
                ],
                'approved_note' => [
                    new StringValue(skipOnEmpty: true),
                    new Length(max: 1000, skipOnEmpty: true),
                ],
                'lock_version' => [
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
                'employee_id' => [
                    new Integer(skipOnEmpty: true),
                ],
                'period' => [
                    new Integer(skipOnEmpty: true),
                ],
                'attendance_summary_id' => [
                    new Integer(skipOnEmpty: true),
                ],
                'source_type' => [
                    new In(ReviewSourceType::values(), skipOnEmpty: true),
                ],
                'review_reason' => [
                    new In(ReviewReason::values(), skipOnEmpty: true),
                ],
                'approval_status' => [
                    new Integer(skipOnEmpty: true),
                ],
                'status' => [
                    new Integer(skipOnEmpty: true),
                ],
                'sync_mdb' => [
                    new Integer(skipOnEmpty: true),
                ],
                'page' => [
                    new Integer(min: 1, skipOnEmpty: true),
                ],
                'page_size' => [
                    new Integer(min: 1, max: 200, skipOnEmpty: true),
                ],
                'sort_by' => [
                    new StringValue(skipOnEmpty: true),
                ],
                'sort_dir' => [
                    new In(['asc', 'desc'], skipOnEmpty: true),
                ],
            ],
            default => [],
        };
    }
}
