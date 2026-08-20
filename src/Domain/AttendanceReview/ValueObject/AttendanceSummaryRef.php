<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReview\ValueObject;

/**
 * Reference to the source AttendanceSummary record.
 *
 * Stored as JSON in the `attendance_summary` column. Captures the id and name
 * of the AttendanceSummary the review was generated from.
 */
final readonly class AttendanceSummaryRef
{
    /** @return array<string, mixed> */
    private static function defaultShape(): array
    {
        return [
            'id' => null,
            'name' => null,
        ];
    }

    private function __construct(
        private array $data,
    ) {}

    public static function empty(): self
    {
        return new self(self::defaultShape());
    }

    public static function fromArray(array $data): self
    {
        return new self(array_replace_recursive(self::defaultShape(), $data));
    }

    public static function fromValues(?int $id, ?string $name): self
    {
        return new self([
            'id' => $id,
            'name' => $name,
        ]);
    }

    public function getId(): ?int
    {
        return $this->data['id'] !== null ? (int) $this->data['id'] : null;
    }

    public function getName(): ?string
    {
        return $this->data['name'];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->data;
    }
}
