<?php

declare(strict_types=1);

namespace App\Application\AttendanceSummary\Dto;

/**
 * Application-level DTO representing the final outcome of a batch
 * AttendanceSummary generation operation (after persistence).
 *
 * Intended for API/console response serialization via toArray().
 */
final readonly class AttendanceSummaryResult
{
    /**
     * @param int $created
     * @param int $skipped
     * @param array<int, string> $errors
     */
    public function __construct(
        public int $created,
        public int $skipped,
        public array $errors,
    ) {}

    /**
     * @param array<int, string> $errors
     */
    public static function create(
        int $created,
        int $skipped,
        array $errors,
    ): self {
        return new self(
            created: $created,
            skipped: $skipped,
            errors: $errors,
        );
    }

    /**
     * @return array{created: int, skipped: int, errors: array<int, string>}
     */
    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
        ];
    }
}
