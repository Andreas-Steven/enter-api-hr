<?php

declare(strict_types=1);

namespace App\Api\V1\AttendanceSummary\Validation;

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
use Yiisoft\Validator\Rule\Nested;

/**
 * AttendanceSummary Input Validator
 * 
 * Menggunakan pattern AbstractValidator dengan ValidationContext
 * untuk validasi input yang berbeda per operation
 */
final class AttendanceSummaryInputValidator extends AbstractValidator
{
    /**
     * AttendanceSummarys for validation rules
     * 
     * This validator shows AttendanceSummarys of advanced validation rules including
     * dependency checking and unique value validation.
     * 
     * @see HasNoDependencies
     * @see UniqueValue
     * 
     * Usage AttendanceSummary for HasNoDependencies:
     * ```php
     * ValidationContext::DELETE => [
     *     'id' => [
     *         new StopOnError([
     *             new Required(),
     *             new Integer(min: 1),
     *             new HasNoDependencies(
     *                 map: [
     *                     'other_table' => ['attendance_summary_id'],
     *                 ],
     *                 message: 'Data tidak bisa dihapus karena masih digunakan di tabel lain.'
     *             ),
     *         ]),
     *     ],
     * ],
     * ```
     * 
     * Usage AttendanceSummary for UniqueValue:
     * ```php
     * ValidationContext::CREATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'AttendanceSummarys',
     *                 column: 'name',
     *                 ignoreId: null,
     *             ),
     *         ]),
     *     ],
     * ],
     * 
     * Usage AttendanceSummary for UniqueValue with exclusion (for updates):
     * ```php
     * ValidationContext::UPDATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'AttendanceSummarys',
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
                'period' => [
                    new Required(),
                    new Integer(min: 1),
                ],
                'notes' => [
                    new StringValue(skipOnEmpty: true),
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
                    new StringValue(skipOnEmpty: true),
                    new Length(min: 3, max: 255, skipOnEmpty: true),
                ],
                'employee_id' => [
                    new Integer(min: 1, skipOnEmpty: true),
                ],
                'branch_id' => [
                    new Integer(min: 1, skipOnEmpty: true),
                ],
                'division_id' => [
                    new Integer(min: 1, skipOnEmpty: true),
                ],
                'position_id' => [
                    new Integer(min: 1, skipOnEmpty: true),
                ],
                'employee_type_id' => [
                    new Integer(min: 1, skipOnEmpty: true),
                ],
                'work_pattern_id' => [
                    new Integer(min: 1, skipOnEmpty: true),
                ],
                'period' => [
                    new Integer(skipOnEmpty: true),
                ],
                'attendance_detail' => [
                    new Nested([], skipOnEmpty: true),
                ],
                'permission_detail' => [
                    new Nested([], skipOnEmpty: true),
                ],
                'period_summary' => [
                    new Nested([
                        'present_days' => [new Integer(min: 0, skipOnEmpty: true)],
                        'absent_days' => [new Integer(min: 0, skipOnEmpty: true)],
                        'leave_days' => [new Integer(min: 0, skipOnEmpty: true)],
                        'sick_days' => [new Integer(min: 0, skipOnEmpty: true)],
                        'permission_days' => [new Integer(min: 0, skipOnEmpty: true)],
                        'hek_count' => [new Integer(min: 0, skipOnEmpty: true)],
                        'valid_present_count' => [new Integer(min: 0, skipOnEmpty: true)],
                        'deficit_count' => [new Integer(min: 0, skipOnEmpty: true)],
                        'excess_count' => [new Integer(min: 0, skipOnEmpty: true)],
                        'overtime_day_count' => [new Integer(min: 0, skipOnEmpty: true)],
                        'leave_quota_used_count' => [new Integer(min: 0, skipOnEmpty: true)],
                        'deduction_day_count' => [new Integer(min: 0, skipOnEmpty: true)],
                    ], skipOnEmpty: true),
                ],
                'time_summary' => [
                    new Nested([
                        'overtime_min_total' => [new Integer(min: 0, skipOnEmpty: true)],
                        'late_count' => [new Integer(min: 0, skipOnEmpty: true)],
                        'late_min_total' => [new Integer(min: 0, skipOnEmpty: true)],
                        'early_out_count' => [new Integer(min: 0, skipOnEmpty: true)],
                        'early_out_min_total' => [new Integer(min: 0, skipOnEmpty: true)],
                        'incomplete_count' => [new Integer(min: 0, skipOnEmpty: true)],
                    ], skipOnEmpty: true),
                ],
                'notes' => [
                    new StringValue(skipOnEmpty: true),
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
                    new Integer(
                        min: 1,
                    ),
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
                'branch_id' => [
                    new Integer(skipOnEmpty: true),
                ],
                'division_id' => [
                    new Integer(skipOnEmpty: true),
                ],
                'position_id' => [
                    new Integer(skipOnEmpty: true),
                ],
                'employee_type_id' => [
                    new Integer(skipOnEmpty: true),
                ],
                'work_pattern_id' => [
                    new Integer(skipOnEmpty: true),
                ],
                'period' => [
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
