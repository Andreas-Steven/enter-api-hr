<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReview\ValueObject;

// Domain Layer
use App\Domain\Shared\Enums\ReviewItemStatus;
use App\Domain\Shared\Enums\ReviewReason;
use App\Domain\Shared\Enums\ReviewSourceType;

/**
 * A single review item inside an AttendanceReview.
 *
 * Each item represents one review-triggering condition found while scanning
 * the source AttendanceSummary:
 *
 *  - DAY_LEVEL:  a specific date where missing_in or missing_out occurred and
 *                the attendance policy's missing_*_policy is SEND_TO_REVIEW.
 *  - RULE_LEVEL: a work-pattern rule where deficit or excess was detected and
 *                the rule's deficit_treatment / excess_treatment requires
 *                manual review or approval.
 *
 * The item captures the evidence (snapshot of the relevant attendance data),
 * the treatment options the reviewer may choose from, and the reviewer's
 * decision once resolved.
 */
final readonly class ReviewItem
{
    private function __construct(
        private string $itemId,
        private ReviewSourceType $sourceType,
        private ReviewReason $reason,
        private ?int $ruleId,
        private ?string $ruleName,
        private ?string $date,
        private string $sourceField,
        private ReviewItemEvidence $evidence,
        private array $treatmentOptions,
        private ReviewItemStatus $status,
        private ReviewItemDecision $decision,
    ) {}

    public static function fromArray(array $data): self
    {
        $sourceType = ReviewSourceType::tryFrom($data['source_type'] ?? '')
            ?? ReviewSourceType::DAY_LEVEL;

        $reason = ReviewReason::tryFrom($data['reason'] ?? '')
            ?? ReviewReason::MISSING_SEND_TO_REVIEW;

        $status = ReviewItemStatus::tryFrom($data['status'] ?? '')
            ?? ReviewItemStatus::PENDING;

        return new self(
            itemId: (string) ($data['item_id'] ?? ''),
            sourceType: $sourceType,
            reason: $reason,
            ruleId: isset($data['rule_id']) ? (int) $data['rule_id'] : null,
            ruleName: $data['rule_name'] ?? null,
            date: $data['date'] ?? null,
            sourceField: (string) ($data['source_field'] ?? ''),
            evidence: ReviewItemEvidence::fromArray($data['evidence'] ?? []),
            treatmentOptions: $data['treatment_options'] ?? [],
            status: $status,
            decision: ReviewItemDecision::fromArray($data['decision'] ?? []),
        );
    }

    /**
     * @param list<string> $treatmentOptions
     */
    public static function fromValues(
        string $itemId,
        ReviewSourceType $sourceType,
        ReviewReason $reason,
        ?int $ruleId,
        ?string $ruleName,
        ?string $date,
        string $sourceField,
        ReviewItemEvidence $evidence,
        array $treatmentOptions,
        ReviewItemStatus $status,
        ReviewItemDecision $decision,
    ): self {
        return new self(
            itemId: $itemId,
            sourceType: $sourceType,
            reason: $reason,
            ruleId: $ruleId,
            ruleName: $ruleName,
            date: $date,
            sourceField: $sourceField,
            evidence: $evidence,
            treatmentOptions: $treatmentOptions,
            status: $status,
            decision: $decision,
        );
    }

    public function getItemId(): string
    {
        return $this->itemId;
    }

    public function getSourceType(): ReviewSourceType
    {
        return $this->sourceType;
    }

    public function getReason(): ReviewReason
    {
        return $this->reason;
    }

    public function getRuleId(): ?int
    {
        return $this->ruleId;
    }

    public function getRuleName(): ?string
    {
        return $this->ruleName;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function getSourceField(): string
    {
        return $this->sourceField;
    }

    public function getEvidence(): ReviewItemEvidence
    {
        return $this->evidence;
    }

    /** @return list<string> */
    public function getTreatmentOptions(): array
    {
        return $this->treatmentOptions;
    }

    public function getStatus(): ReviewItemStatus
    {
        return $this->status;
    }

    public function getDecision(): ReviewItemDecision
    {
        return $this->decision;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'item_id' => $this->itemId,
            'source_type' => $this->sourceType->value,
            'reason' => $this->reason->value,
            'rule_id' => $this->ruleId,
            'rule_name' => $this->ruleName,
            'date' => $this->date,
            'source_field' => $this->sourceField,
            'evidence' => $this->evidence->toArray(),
            'treatment_options' => $this->treatmentOptions,
            'status' => $this->status->value,
            'decision' => $this->decision->toArray(),
        ];
    }
}
