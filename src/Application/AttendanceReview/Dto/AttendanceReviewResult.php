<?php

declare(strict_types=1);

namespace App\Application\AttendanceReview\Dto;

/**
 * Application-level DTO representing the final outcome of a batch
 * AttendanceReview generation operation (after persistence).
 *
 * Intended for API/console response serialization via toArray().
 */
final readonly class AttendanceReviewResult
{
    /**
     * @param int $created
     * @param int $skipped
     * @param int $completed  summaries with no review-triggering conditions (no review created)
     * @param array<int, string> $errors
     */
    public function __construct(
        public int $created,
        public int $skipped,
        public int $completed,
        public array $errors,
    ) {}

    /**
     * @param array<int, string> $errors
     */
    public static function create(
        int $created,
        int $skipped,
        int $completed,
        array $errors,
    ): self {
        return new self(
            created: $created,
            skipped: $skipped,
            completed: $completed,
            errors: $errors,
        );
    }

    /**
     * @return array{created: int, skipped: int, completed: int, errors: array<int, string>}
     */
    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'skipped' => $this->skipped,
            'completed' => $this->completed,
            'errors' => $this->errors,
        ];
    }
}
