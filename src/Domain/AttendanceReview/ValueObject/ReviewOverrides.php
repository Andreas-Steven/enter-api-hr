<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReview\ValueObject;

use App\Domain\AttendanceReview\Entity\AttendanceReview;
use App\Domain\Shared\Enums\ApprovalStatus;
use App\Domain\Shared\Enums\ReviewSourceType;

/**
 * Immutable map of resolved review overrides keyed by stable hash.
 *
 * Built from a collection of AttendanceReview entities that have been
 * APPROVED. Consumed by AttendanceSummaryDomainService::calculateSummaries()
 * to substitute the original source treatment with the resolver's chosen
 * treatment during recalculation.
 */
final class ReviewOverrides
{
    /**
     * @param array<string, string> $overrides Map of stable_hash => resolved_treatment
     * @param array<string, array{source_type: string, source_ref: array, resolved_treatment: string}> $details Full detail for day-level application
     */
    private function __construct(
        private readonly array $overrides,
        private readonly array $details,
    ) {}

    public static function empty(): self
    {
        return new self(overrides: [], details: []);
    }

    /**
     * @param AttendanceReview[] $reviews
     */
    public static function fromReviews(array $reviews): self
    {
        $overrides = [];
        $details = [];

        foreach ($reviews as $review) {
            if ($review->getApprovalStatus() !== ApprovalStatus::APPROVED) {
                continue;
            }
            if ($review->getResolvedTreatment() === null) {
                continue;
            }

            $hash = $review->getStableKey();
            $overrides[$hash] = $review->getResolvedTreatment();
            $details[$hash] = [
                'source_type' => $review->getSourceType()->value,
                'source_ref' => $review->getSourceRef()->toArray(),
                'resolved_treatment' => $review->getResolvedTreatment(),
            ];
        }

        return new self(overrides: $overrides, details: $details);
    }

    /**
     * Returns the override treatment for a rule-level stable hash, or null
     * if no approved override exists.
     */
    public function forRule(int $ruleId, string $reviewReason): ?string
    {
        // Build a candidate hash for the rule and look it up.
        // We try both deficit and excess shapes since the reason determines
        // which counts are non-zero; the caller knows the reason.
        foreach ($this->details as $hash => $detail) {
            if ($detail['source_type'] !== ReviewSourceType::RULE_LEVEL->value) {
                continue;
            }
            $ref = $detail['source_ref'];
            if ((int) ($ref['rule_id'] ?? 0) !== $ruleId) {
                continue;
            }
            return $detail['resolved_treatment'];
        }
        return null;
    }

    /**
     * Returns all day-level overrides, keyed by date.
     *
     * @return array<string, array{missing_in: bool, missing_out: bool, resolved_treatment: string}>
     */
    public function forDays(): array
    {
        $result = [];
        foreach ($this->details as $detail) {
            if ($detail['source_type'] !== ReviewSourceType::DAY_LEVEL->value) {
                continue;
            }
            $ref = $detail['source_ref'];
            $date = (string) ($ref['date'] ?? '');
            if ($date === '') {
                continue;
            }
            $result[$date] = [
                'missing_in' => (bool) ($ref['missing_in'] ?? false),
                'missing_out' => (bool) ($ref['missing_out'] ?? false),
                'resolved_treatment' => $detail['resolved_treatment'],
            ];
        }
        return $result;
    }

    /**
     * Returns the raw override map (stable_hash => resolved_treatment).
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->overrides;
    }

    public function isEmpty(): bool
    {
        return $this->overrides === [];
    }
}
