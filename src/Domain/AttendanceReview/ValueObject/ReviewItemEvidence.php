<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReview\ValueObject;

// Domain Layer
use App\Domain\Shared\Enums\ReviewReason;
use App\Domain\Shared\Enums\ReviewSourceType;

/**
 * Snapshot of the evidence that triggered a single AttendanceReview case.
 *
 * Stored as JSON in the `evidence` column. Captures the full context of the
 * review-triggering condition at generation time so that the review decision
 * remains auditable even if the source AttendanceSummary is later regenerated.
 *
 * Each AttendanceReview record contains exactly one Evidence — one case per
 * row — so the item_id / seq numbering is no longer needed.
 */
final readonly class ReviewItemEvidence
{
    private function __construct(
        private ReviewSourceType $sourceType,
        private ReviewReason $reason,
        private ?int $ruleId,
        private ?string $ruleName,
        private ?string $date,
        private string $sourceField,
        private ?int $workMinutes,
        private ?int $requiredMinutes,
        private ?array $shift,
        private array $detail,
        private array $treatmentOptions,
    ) {}

    public static function empty(): self
    {
        return new self(
            sourceType: ReviewSourceType::DAY_LEVEL,
            reason: ReviewReason::MISSING_SEND_TO_REVIEW,
            ruleId: null,
            ruleName: null,
            date: null,
            sourceField: '',
            workMinutes: null,
            requiredMinutes: null,
            shift: null,
            detail: [],
            treatmentOptions: [],
        );
    }

    public static function fromArray(array $data): self
    {
        $sourceType = ReviewSourceType::tryFrom($data['source_type'] ?? '')
            ?? ReviewSourceType::DAY_LEVEL;

        $reason = ReviewReason::tryFrom($data['reason'] ?? '')
            ?? ReviewReason::MISSING_SEND_TO_REVIEW;

        return new self(
            sourceType: $sourceType,
            reason: $reason,
            ruleId: isset($data['rule_id']) ? (int) $data['rule_id'] : null,
            ruleName: $data['rule_name'] ?? null,
            date: $data['date'] ?? null,
            sourceField: (string) ($data['source_field'] ?? ''),
            workMinutes: isset($data['work_minutes'])
                ? (int) $data['work_minutes']
                : null,
            requiredMinutes: isset($data['required_minutes'])
                ? (int) $data['required_minutes']
                : null,
            shift: $data['shift'] ?? null,
            detail: $data['detail'] ?? [],
            treatmentOptions: $data['treatment_options'] ?? [],
        );
    }

    /**
     * @param array<string, mixed>|null $shift
     * @param array<string, mixed> $detail
     * @param list<string> $treatmentOptions
     */
    public static function fromValues(
        ReviewSourceType $sourceType,
        ReviewReason $reason,
        ?int $ruleId,
        ?string $ruleName,
        ?string $date,
        string $sourceField,
        ?int $workMinutes,
        ?int $requiredMinutes,
        ?array $shift,
        array $detail,
        array $treatmentOptions,
    ): self {
        return new self(
            sourceType: $sourceType,
            reason: $reason,
            ruleId: $ruleId,
            ruleName: $ruleName,
            date: $date,
            sourceField: $sourceField,
            workMinutes: $workMinutes,
            requiredMinutes: $requiredMinutes,
            shift: $shift,
            detail: $detail,
            treatmentOptions: $treatmentOptions,
        );
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

    public function getWorkMinutes(): ?int
    {
        return $this->workMinutes;
    }

    public function getRequiredMinutes(): ?int
    {
        return $this->requiredMinutes;
    }

    /** @return array<string, mixed>|null */
    public function getShift(): ?array
    {
        return $this->shift;
    }

    /** @return array<string, mixed> */
    public function getDetail(): array
    {
        return $this->detail;
    }

    /** @return list<string> */
    public function getTreatmentOptions(): array
    {
        return $this->treatmentOptions;
    }

    /**
     * Builds a stable unique key for this evidence, used for idempotent
     * naming during generation.
     *
     * DAY_LEVEL:  D{date}-{sourceField}
     * RULE_LEVEL: R{ruleId}-{sourceField}
     */
    public function uniqueKey(): string
    {
        if ($this->sourceType === ReviewSourceType::RULE_LEVEL) {
            return sprintf('R%d-%s', $this->ruleId ?? 0, $this->sourceField);
        }

        $datePart = $this->date ?? '0000-00-00';
        return sprintf('D%s-%s', $datePart, $this->sourceField);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source_type' => $this->sourceType->value,
            'reason' => $this->reason->value,
            'rule_id' => $this->ruleId,
            'rule_name' => $this->ruleName,
            'date' => $this->date,
            'source_field' => $this->sourceField,
            'work_minutes' => $this->workMinutes,
            'required_minutes' => $this->requiredMinutes,
            'shift' => $this->shift,
            'detail' => $this->detail,
            'treatment_options' => $this->treatmentOptions,
            'treatment_options_detail' => $this->reason->detailedTreatmentOptions(),
        ];
    }
}
