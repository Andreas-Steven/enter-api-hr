<?php

declare(strict_types=1);

namespace App\Application\AttendanceReview\Dto;

/**
 * Result of a batch review generation operation.
 */
final readonly class BatchGenerationResult
{
    public function __construct(
        public int $created,
        public int $skipped,
        public array $errors,
    ) {}

    public static function create(
        int $created,
        int $skipped = 0,
        array $errors = [],
    ): self {
        return new self(
            created: $created,
            skipped: $skipped,
            errors: $errors,
        );
    }

    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
        ];
    }
}
