<?php

declare(strict_types=1);

namespace App\Application\PayrollReport\Dto;

/**
 * Application-level DTO representing the final outcome of a batch
 * PayrollReport generation operation (after persistence).
 *
 * Intended for API/console response serialization via toArray().
 */
final readonly class PayrollReportResult
{
    /**
     * @param int $created
     * @param int $skipped
     * @param int $updated
     * @param array<int, string> $errors
     */
    public function __construct(
        public int $created,
        public int $skipped,
        public int $updated,
        public array $errors,
    ) {}

    /**
     * @param array<int, string> $errors
     */
    public static function create(
        int $created,
        int $skipped,
        int $updated,
        array $errors,
    ): self {
        return new self(
            created: $created,
            skipped: $skipped,
            updated: $updated,
            errors: $errors,
        );
    }

    /**
     * @return array{created: int, skipped: int, updated: int, errors: array<int, string>}
     */
    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'skipped' => $this->skipped,
            'updated' => $this->updated,
            'errors' => $this->errors,
        ];
    }
}
