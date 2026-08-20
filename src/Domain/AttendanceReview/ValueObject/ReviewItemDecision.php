<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReview\ValueObject;

/**
 * The reviewer's decision on a single review item.
 *
 * Stored as JSON inside each item's `decision` field. Populated when a
 * reviewer resolves the item by choosing a treatment from the item's
 * `treatment_options`.
 */
final readonly class ReviewItemDecision
{
    private function __construct(
        private ?string $resolvedTreatment,
        private ?int $resolvedBy,
        private ?string $resolvedAt,
        private ?string $note,
    ) {}

    public static function empty(): self
    {
        return new self(null, null, null, null);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            resolvedTreatment: $data['resolved_treatment'] ?? null,
            resolvedBy: isset($data['resolved_by'])
                ? (int) $data['resolved_by']
                : null,
            resolvedAt: $data['resolved_at'] ?? null,
            note: $data['note'] ?? null,
        );
    }

    public static function fromValues(
        ?string $resolvedTreatment,
        ?int $resolvedBy,
        ?string $resolvedAt,
        ?string $note,
    ): self {
        return new self($resolvedTreatment, $resolvedBy, $resolvedAt, $note);
    }

    public function getResolvedTreatment(): ?string
    {
        return $this->resolvedTreatment;
    }

    public function getResolvedBy(): ?int
    {
        return $this->resolvedBy;
    }

    public function getResolvedAt(): ?string
    {
        return $this->resolvedAt;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function isEmpty(): bool
    {
        return $this->resolvedTreatment === null
            && $this->resolvedBy === null
            && $this->resolvedAt === null
            && $this->note === null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'resolved_treatment' => $this->resolvedTreatment,
            'resolved_by' => $this->resolvedBy,
            'resolved_at' => $this->resolvedAt,
            'note' => $this->note,
        ];
    }
}
