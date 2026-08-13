<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

use InvalidArgumentException;

/**
 * Maps a source treatment (from DeficitTreatment / ExcessTreatment / MissingPolicy)
 * to a unified review reason. This decouples the AttendanceReview aggregate from
 * the individual source enums while preserving traceability.
 */
enum ReviewReason: string
{
    case DEFICIT_MANUAL_REVIEW = 'DEFICIT_MANUAL_REVIEW';
    case EXCESS_REQUIRE_APPROVAL = 'EXCESS_REQUIRE_APPROVAL';
    case MISSING_SEND_TO_REVIEW = 'MISSING_SEND_TO_REVIEW';

    public function label(): string
    {
        return match ($this) {
            self::DEFICIT_MANUAL_REVIEW => 'Deficit Manual Review',
            self::EXCESS_REQUIRE_APPROVAL => 'Excess Require Approval',
            self::MISSING_SEND_TO_REVIEW => 'Missing Send To Review',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Factory: builds a ReviewReason from the raw source treatment string.
     *
     * Accepts the case value of any of the three source enums:
     * - DeficitTreatment::MANUAL_REVIEW->value
     * - ExcessTreatment::REQUIRE_APPROVAL->value
     * - MissingPolicy::SEND_TO_REVIEW->value
     *
     * @throws InvalidArgumentException when the treatment is not a review-triggering value.
     */
    public static function fromSourceTreatment(string $treatment): self
    {
        return match ($treatment) {
            'MANUAL_REVIEW' => self::DEFICIT_MANUAL_REVIEW,
            'REQUIRE_APPROVAL' => self::EXCESS_REQUIRE_APPROVAL,
            'SEND_TO_REVIEW' => self::MISSING_SEND_TO_REVIEW,
            default => throw new InvalidArgumentException(
                sprintf('Treatment "%s" does not map to a ReviewReason.', $treatment)
            ),
        };
    }

    /**
     * Returns the source enum class that produces this reason.
     *
     * @return class-string
     */
    public function sourceEnumClass(): string
    {
        return match ($this) {
            self::DEFICIT_MANUAL_REVIEW => DeficitTreatment::class,
            self::EXCESS_REQUIRE_APPROVAL => ExcessTreatment::class,
            self::MISSING_SEND_TO_REVIEW => MissingPolicy::class,
        };
    }
}
