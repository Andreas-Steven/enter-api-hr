<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReview\ValueObject;

/**
 * Snapshot of the evidence that triggered a review item.
 *
 * Stored as JSON inside each item's `evidence` field. Captures the raw
 * attendance data for the relevant day/rule at generation time so that the
 * review decision remains auditable even if the source AttendanceSummary is
 * later regenerated.
 */
final readonly class ReviewItemEvidence
{
    private function __construct(
        private ?int $workMinutes,
        private ?int $requiredMinutes,
        private ?array $shift,
        private array $detail,
    ) {}

    public static function empty(): self
    {
        return new self(null, null, null, []);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            workMinutes: isset($data['work_minutes'])
                ? (int) $data['work_minutes']
                : null,
            requiredMinutes: isset($data['required_minutes'])
                ? (int) $data['required_minutes']
                : null,
            shift: $data['shift'] ?? null,
            detail: $data['detail'] ?? [],
        );
    }

    /**
     * @param array<string, mixed>|null $shift
     * @param array<string, mixed> $detail
     */
    public static function fromValues(
        ?int $workMinutes,
        ?int $requiredMinutes,
        ?array $shift,
        array $detail,
    ): self {
        return new self($workMinutes, $requiredMinutes, $shift, $detail);
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

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'work_minutes' => $this->workMinutes,
            'required_minutes' => $this->requiredMinutes,
            'shift' => $this->shift,
            'detail' => $this->detail,
        ];
    }
}
