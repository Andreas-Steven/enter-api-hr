<?php

declare(strict_types=1);

namespace App\Domain\PayrollReport\ValueObject;

/**
 * Snapshot of the employee a payroll report is generated for.
 *
 * Stored as JSON in the `employee` column. Mirrors the AttendanceSummary
 * employee snapshot so the payroll report stays readable even if the employee
 * master data changes afterwards.
 */
final readonly class EmployeeSnapshot
{
    /** @return array<string, mixed> */
    private static function defaultShape(): array
    {
        return [
            'id' => null,
            'name' => null,
            'type' => ['id' => null, 'name' => null],
            'position' => ['id' => null, 'name' => null],
            'division' => ['id' => null, 'code' => null, 'name' => null],
            'branch' => ['id' => null, 'code' => null, 'name' => null],
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

    public function getEmployeeId(): ?int
    {
        return $this->data['id'] !== null ? (int) $this->data['id'] : null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->data;
    }
}
