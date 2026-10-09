<?php

declare(strict_types=1);

namespace App\Api\V1\GeneralAffair\Validation;

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
use Yiisoft\Validator\Rule\Each;
use Yiisoft\Validator\Rule\Nested;
use Yiisoft\Validator\Rule\Number;

/**
 * GeneralAffair Input Validator
 * 
 * Menggunakan pattern AbstractValidator dengan ValidationContext
 * untuk validasi input yang berbeda per operation
 */

/**
 * TODO:
 * [ ] Asset rule for CREATE 
 */
final class GeneralAffairInputValidator extends AbstractValidator
{
    /**
     * GeneralAffairs for validation rules
     * 
     * This validator shows generalaffairs of advanced validation rules including
     * dependency checking and unique value validation.
     * 
     * @see HasNoDependencies
     * @see UniqueValue
     * 
     * Usage generalaffair for HasNoDependencies:
     * ```php
     * ValidationContext::DELETE => [
     *     'id' => [
     *         new StopOnError([
     *             new Required(),
     *             new Integer(min: 1),
     *             new HasNoDependencies(
     *                 map: [
     *                     'other_table' => ['general_affair_id'],
     *                 ],
     *                 message: 'Data tidak bisa dihapus karena masih digunakan di tabel lain.'
     *             ),
     *         ]),
     *     ],
     * ],
     * ```
     * 
     * Usage generalaffair for UniqueValue:
     * ```php
     * ValidationContext::CREATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'generalaffairs',
     *                 column: 'name',
     *                 ignoreId: null,
     *             ),
     *         ]),
     *     ],
     * ],
     * 
     * Usage generalaffair for UniqueValue with exclusion (for updates):
     * ```php
     * ValidationContext::UPDATE => [
     *     'name' => [
     *         new StopOnError([
     *             new Required(),
     *             new StringLength(max: 255),
     *             new UniqueValue(
     *                 table: 'generalaffairs',
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
                /* 'name' => [
                    new StopOnError([
                        new Required(),
                        new StringValue(),
                        new Length(min: 1, max: 255),
                    ])
                ], */
                'product_id' => [
                    new StopOnError([
                        new Required(),
                        new Integer(),
                        // new UniqueValue(
                        //     table: 'general_affair', 
                        //     column: 'product_id', 
                        //     ignoreId: null
                        // )
                    ])
                ],
                'category_id' => [
                    new StopOnError([
                        new Required(),
                        new Integer(),
                        // new UniqueValue(
                        //     table: 'general_affair', 
                        //     column: 'category_id', 
                        //     ignoreId: null
                        // )
                    ])
                ],
                'location_id' => [
                    new StopOnError([
                        new Required(),
                        new Integer(),
                        // new UniqueValue(
                        //     table: 'general_affair', 
                        //     column: 'location_id', 
                        //     ignoreId: null
                        // )
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
                /* 'name' => [
                    new StopOnError([
                        new StringValue(skipOnEmpty: true),
                        new Length(min: 1, max: 255, skipOnEmpty: true),
                    ])
                ], */
                'product_id' => [
                    new StopOnError([
                        new Integer(
                            skipOnEmpty: true,
                        ),
                        new UniqueValue(
                            table: 'general_affair', 
                            column: 'product_id', 
                            ignoreId: $this->data['id'] ?? null
                        )
                    ])
                ],
                'category_id' => [
                    new StopOnError([
                        new Integer(
                            skipOnEmpty: true,
                        ),
                        new UniqueValue(
                            table: 'general_affair', 
                            column: 'category_id', 
                            ignoreId: $this->data['id'] ?? null
                        )
                    ])
                ],
                'location_id' => [
                    new StopOnError([
                        new Integer(
                            skipOnEmpty: true,
                        ),
                        new UniqueValue(
                            table: 'general_affair', 
                            column: 'location_id', 
                            ignoreId: $this->data['id'] ?? null
                        )
                    ])
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
                'asset' => [
                    new Nested([
                        'items' => [
                            new Each([
                                new Nested([
                                    'code' => [
                                        new StringValue(
                                            skipOnEmpty: true,
                                        ),
                                    ],
                                    'price' => [
                                        new Number(
                                            skipOnEmpty: true,
                                        ),
                                    ],
                                    'condition' => [
                                        new Integer(
                                            skipOnEmpty: true,
                                        ),
                                    ],
                                    'description' => [
                                        new StringValue(
                                            skipOnEmpty: true,
                                        ),
                                    ],
                                    'buying_date' => [
                                        new StringValue(
                                            skipOnEmpty: true,
                                        ),
                                    ],
                                    'tax_date' => [
                                        new StringValue(
                                            skipOnEmpty: true,
                                        ),
                                    ],
                                    'inspection_date' => [
                                        new StringValue(
                                            skipOnEmpty: true,
                                        ),
                                    ],
                                    'employee' => [
                                        new Nested([
                                            'id' => [
                                                new Integer(
                                                    skipOnEmpty: true,
                                                ),
                                            ],
                                        ], skipOnEmpty: true),
                                    ],
                                    'division' => [
                                        new Nested([
                                            'id' => [
                                                new Integer(
                                                    skipOnEmpty: true,
                                                ),
                                            ],
                                        ], skipOnEmpty: true),
                                    ],
                                    'image' => [
                                        new Each([
                                            new StringValue(
                                                skipOnEmpty: true,
                                            ),
                                        ], skipOnEmpty: true),
                                    ],
                                ], skipOnEmpty: true),
                            ], skipOnEmpty: true),
                        ],
                    ], skipOnEmpty: true),
                ],
                /* 'name' => [
                    new StringValue(
                        skipOnEmpty: true,
                    ),
                    new Length(
                        min: 1,
                        max: 100,
                        skipOnEmpty: true,
                    ),
                ], */
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
