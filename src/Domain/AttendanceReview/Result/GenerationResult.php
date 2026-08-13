<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReview\Result;

use App\Domain\AttendanceReview\Entity\AttendanceReview;

/**
 * Result of scanning attendance data for review-triggering treatments.
 *
 * Contains the built review entities (newly created), the count of
 * items skipped because a review already exists for that stable key,
 * and any errors encountered during generation.
 */
final class GenerationResult
{
    /**
     * @param AttendanceReview[] $entities
     * @param int $skippedCount
     * @param array<int, string> $errors
     */
    private function __construct(
        public readonly array $entities,
        public readonly int $skippedCount,
        public readonly array $errors,
    ) {}

    /**
     * @param AttendanceReview[] $entities
     * @param array<int, string> $errors
     */
    public static function create(
        array $entities,
        int $skippedCount = 0,
        array $errors = [],
    ): self {
        return new self(
            entities: $entities,
            skippedCount: $skippedCount,
            errors: $errors,
        );
    }
}
