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

    /**
     * Returns the source treatment value that triggers this review reason.
     *
     * Used to compute the treatment_options for a review item: all cases of
     * the source enum except this triggering value.
     */
    public function sourceTriggerValue(): string
    {
        return match ($this) {
            self::DEFICIT_MANUAL_REVIEW => 'MANUAL_REVIEW',
            self::EXCESS_REQUIRE_APPROVAL => 'REQUIRE_APPROVAL',
            self::MISSING_SEND_TO_REVIEW => 'SEND_TO_REVIEW',
        };
    }

    /**
     * Returns the treatment options available for a review item with this
     * reason, as list of string values.
     *
     * Delegates to {@see ReviewTreatment::optionsForReason()} for the
     * explicit per-reason mapping.
     *
     * @return list<string>
     */
    public function treatmentOptions(): array
    {
        return array_map(
            static fn(ReviewTreatment $t): string => $t->value,
            ReviewTreatment::optionsForReason($this),
        );
    }

    /**
     * Returns the treatment options for this reason as detailed array
     * (value, label, description) for API responses.
     *
     * @return list<array{value: string, label: string, description: string}>
     */
    public function detailedTreatmentOptions(): array
    {
        return ReviewTreatment::detailedOptionsForReason($this);
    }
}
