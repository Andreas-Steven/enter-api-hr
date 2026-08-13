<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReview\ValueObject;

use App\Domain\Shared\Enums\ReviewSourceType;
use JsonSerializable;

/**
 * Immutable Value Object that captures the origin of a review item.
 *
 * Two shapes are supported, discriminated by ReviewSourceType:
 *
 *  RULE_LEVEL — represents a deficit/excess aggregate for a single rule
 *    over a period. Fields: rule_id, rule_type, target_count, fulfilled_count,
 *    deficit_count, excess_count.
 *
 *  DAY_LEVEL — represents a missing check-in/out event on a specific date.
 *    Fields: date, missing_in (bool), missing_out (bool).
 *
 * The stable hash is derived from (source_type + canonical payload) and is
 * used as the unique key for keep-by-stable-key regeneration semantics.
 */
final class SourceRef implements JsonSerializable
{
    private function __construct(
        private readonly ReviewSourceType $sourceType,
        private readonly array $payload,
    ) {}

    public static function forRule(
        int $ruleId,
        ?string $ruleType,
        int $targetCount,
        int $fulfilledCount,
        int $deficitCount,
        int $excessCount,
    ): self {
        return new self(
            sourceType: ReviewSourceType::RULE_LEVEL,
            payload: [
                'rule_id' => $ruleId,
                'rule_type' => $ruleType,
                'target_count' => $targetCount,
                'fulfilled_count' => $fulfilledCount,
                'deficit_count' => $deficitCount,
                'excess_count' => $excessCount,
            ],
        );
    }

    public static function forDay(
        string $date,
        bool $missingIn,
        bool $missingOut,
    ): self {
        return new self(
            sourceType: ReviewSourceType::DAY_LEVEL,
            payload: [
                'date' => $date,
                'missing_in' => $missingIn,
                'missing_out' => $missingOut,
            ],
        );
    }

    public static function fromArray(array $data): self
    {
        $sourceType = ReviewSourceType::from((string) ($data['source_type'] ?? ''));

        // Strip source_type from payload — it lives on the wrapper, not inside.
        unset($data['source_type']);

        return new self(
            sourceType: $sourceType,
            payload: $data,
        );
    }

    public function getSourceType(): ReviewSourceType
    {
        return $this->sourceType;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getRuleId(): ?int
    {
        return $this->sourceType === ReviewSourceType::RULE_LEVEL
            ? (int) ($this->payload['rule_id'] ?? 0)
            : null;
    }

    public function getDate(): ?string
    {
        return $this->sourceType === ReviewSourceType::DAY_LEVEL
            ? (string) ($this->payload['date'] ?? '')
            : null;
    }

    /**
     * Canonical, deterministic hash used as the stable key.
     *
     * The hash is computed from the source type and a sorted-key JSON
     * representation of the payload, ensuring the same logical source
     * always produces the same key regardless of array ordering.
     */
    public function stableHash(): string
    {
        $canonical = $this->sourceType->value . ':' . json_encode(
            $this->sortKeysRecursive($this->payload),
        );

        return sha1($canonical);
    }

    public function toArray(): array
    {
        return array_merge(
            ['source_type' => $this->sourceType->value],
            $this->payload,
        );
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private function sortKeysRecursive(array $data): array
    {
        ksort($data);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sortKeysRecursive($value);
            }
        }
        return $data;
    }
}
