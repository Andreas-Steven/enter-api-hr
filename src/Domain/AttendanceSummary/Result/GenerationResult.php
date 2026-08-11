<?php

declare(strict_types=1);

namespace App\Domain\AttendanceSummary\Result;

use App\Domain\AttendanceSummary\Entity\AttendanceSummary;

/**
 * Result of a batch generation operation at the domain level.
 *
 * Contains the successfully built entities (not yet persisted),
 * the count of skipped employees (domain rule violations), and
 * any errors encountered during generation.
 *
 * Persistence is NOT part of this result — that is the application
 * service's responsibility.
 */
final readonly class GenerationResult
{
    /**
     * @param AttendanceSummary[] $entities
     * @param int $skippedCount
     * @param array<int, string> $errors
     */
    public function __construct(
        public array $entities,
        public int $skippedCount,
        public array $errors,
    ) {}

    /**
     * @param AttendanceSummary[] $entities
     * @param array<int, string> $errors
     */
    public static function create(
        array $entities,
        int $skippedCount,
        array $errors,
    ): self {
        return new self(
            entities: $entities,
            skippedCount: $skippedCount,
            errors: $errors,
        );
    }
}
