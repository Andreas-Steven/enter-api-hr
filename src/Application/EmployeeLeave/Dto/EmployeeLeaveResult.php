<?php

declare(strict_types=1);

namespace App\Application\EmployeeLeave\Dto;

/**
 * Application-level DTO representing the final outcome of a batch
 * EmployeeLeave grand-assign operation (after persistence).
 *
 * Exposes two separate payloads for API serialization:
 *  - data(): list of created/updated EmployeeLeave records (same shape as a
 *    single create response), suitable for the response "data" field.
 *  - meta(): aggregate counts (created, adjusted, skipped) and per-employee
 *    errors, suitable for the response "meta" field.
 */
final readonly class EmployeeLeaveResult
{
    /**
     * @param int $created  number of new EmployeeLeave records created
     * @param int $adjusted number of existing records updated (adjustment_amount increased)
     * @param int $skipped  number of employees skipped (e.g. already had exact same name)
     * @param array<int, string> $errors
     * @param list<array<string, mixed>> $items
     */
    public function __construct(
        public int $created,
        public int $adjusted,
        public int $skipped,
        public array $errors,
        public array $items,
    ) {}

    /**
     * @param array<int, string> $errors
     * @param list<array<string, mixed>> $items
     */
    public static function create(
        int $created,
        int $adjusted,
        int $skipped,
        array $errors,
        array $items,
    ): self {
        return new self(
            created: $created,
            adjusted: $adjusted,
            skipped: $skipped,
            errors: $errors,
            items: $items,
        );
    }

    /**
     * Returns the list of created/updated EmployeeLeave records, shaped like a
     * normal create response. Intended for the response "data" field.
     *
     * @return list<array<string, mixed>>
     */
    public function data(): array
    {
        return $this->items;
    }

    /**
     * Returns aggregate metadata about the batch operation. Intended for the
     * response "meta" field.
     *
     * @return array{created: int, adjusted: int, skipped: int, errors: array<int, string>}
     */
    public function meta(): array
    {
        return [
            'created'  => $this->created,
            'adjusted' => $this->adjusted,
            'skipped'  => $this->skipped,
            'errors'   => $this->errors,
        ];
    }

    /**
     * Legacy flat serialization. Kept for backward compatibility with any
     * consumer that still calls toArray() directly.
     *
     * @return array{created: int, adjusted: int, skipped: int, errors: array<int, string>, items: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'created'  => $this->created,
            'adjusted' => $this->adjusted,
            'skipped'  => $this->skipped,
            'errors'   => $this->errors,
            'items'    => $this->items,
        ];
    }
}
