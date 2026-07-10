<?php

declare(strict_types=1);

namespace App\Api\V1\AttendanceCalendar\Validation;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Validation\AbstractValidator;
use App\Shared\Context\ValidationContext;
use App\Shared\Validation\Rules\HasNoDependencies;
use App\Shared\Validation\Rules\UniqueValue;
use App\Shared\Validation\Rules\Exists;

// Vendor Layer
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\StringValue;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\StopOnError;
use Yiisoft\Validator\Rule\Each;
use Yiisoft\Validator\Rule\Nested;

/**
 * AttendanceCalendar Input Validator
 * 
 * Menggunakan pattern AbstractValidator dengan ValidationContext
 * untuk validasi input yang berbeda per operation
 */
final class AttendanceCalendarInputValidator extends AbstractValidator
{
    /**
     * AttendanceCalendars for validation rules
     * 
     * This validator shows attendancecalendars of advanced validation rules including
     * dependency checking and unique value validation.
     * 
     * @see HasNoDependencies
     * @see UniqueValue
     * 
     * Usage attendancecalendar for HasNoDependencies:
     * ```php
     * ValidationContext::DELETE => [
     *     'id' => [
     *         new StopOnError([
     *             new Required(),
     *             new Integer(min: 1),
     *             new HasNoDependencies(
     *                 map: [
     *                     'other_table' => ['attendance_calendar_id'],
     *                 ],
     *                 message: 'Data tidak bisa dihapus karena masih digunakan di tabel lain.'
     *             ),
     *         ]),
     *     ],
     * ],
     * ```
     * 
     * Usage attendancecalendar for UniqueValue:
     * ```php
     * ValidationContext::CREATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'attendancecalendars',
     *                 column: 'name',
     *                 ignoreId: null,
     *             ),
     *         ]),
     *     ],
     * ],
     * 
     * Usage attendancecalendar for UniqueValue with exclusion (for updates):
     * ```php
     * ValidationContext::UPDATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'attendancecalendars',
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
                            table: 'attendance_calendar', 
                            column: 'name', 
                            ignoreId: null
                        )
                    ])
                ],
                'date' => [
                    new Required(),
                    new StringValue(),
                ],
                'holiday_type' => [
                    new Required(),
                    new StringValue(),
                    new In([
                        'COMPANY_HOLIDAY',
                        'WORKING_DAY',
                        'SPECIAL_DAY',
                        'WEEKLY_HOLIDAY',
                        'NATIONAL_HOLIDAY',
                        'HALF_DAY',
                    ]),
                ],
                'scope' => [
                    new Nested([
                        'branch_id' => [
                            new StopOnError([
                                new Integer(
                                    min: 1,
                                    skipOnEmpty: true,
                                ),
                            ]),
                        ],
                    ]),
                ],
                'status' => [
                    new Required(),
                    new Integer(),
                    new In(
                        RecordStatus::draftOnlyStates(),
                    ),
                ],
                'note' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                ],
                'day_off' => [
                    new Integer(
                        skipOnEmpty: true,
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
                            table: 'attendance_calendar', 
                            column: 'name', 
                            ignoreId: $this->data['id'] ?? null
                        )
                    ])
                ],
                'date' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                ],
                'holiday_type' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                    new In([
                        'COMPANY_HOLIDAY',
                        'WORKING_DAY',
                        'SPECIAL_DAY',
                        'WEEKLY_HOLIDAY',
                        'NATIONAL_HOLIDAY',
                        'HALF_DAY',
                    ], skipOnEmpty: true),
                ],
                'scope' => [
                    new Nested([
                        'branch_id' => [
                            new StopOnError([
                                new Integer(
                                    min: 1,
                                    skipOnEmpty: true,
                                ),
                            ]),
                        ],
                    ], skipOnEmpty: true),
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
                'note' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                ],
                'day_off' => [
                    new Integer(
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
                'date' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                ],
                'period' => [
                    new Integer(
                        skipOnEmpty: true,
                    ),
                ],
                'holiday_type' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                ],
                'scope' => [
                    new Each([
                        new Nested([
                            'id' => [
                                new Integer(
                                    min: 1,
                                    skipOnEmpty: true,
                                ),
                            ],
                        ]),
                    ], skipOnEmpty: true),
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
                'day_off' => [
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
