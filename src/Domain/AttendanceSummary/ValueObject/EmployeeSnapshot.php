<?php

declare(strict_types=1);

namespace App\Domain\AttendanceSummary\ValueObject;

// Domain Layer
use App\Domain\Employee\Entity\Employee;

/**
 * Snapshot of the employee a summary is generated for.
 *
 * Stored as JSON in the `employee` column. Captures identifying data at
 * generation time so the summary stays readable even if the employee master
 * data changes afterwards.
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

    public static function fromEmployee(Employee $employee): self
    {
        $detailInfo = $employee->getDetailInfo();

        $type = $detailInfo->get('employee_type') ?? [
            'id' => $employee->getEmployeeTypeId(),
            'name' => null,
        ];

        $position = $detailInfo->get('position') ?? [
            'id' => $employee->getPositionId(),
            'name' => null,
        ];

        return new self([
            'id' => $employee->getId(),
            'name' => $employee->getName(),
            'type' => [
                'id' => $type['id'] ?? $employee->getEmployeeTypeId(),
                'name' => $type['name'] ?? null,
            ],
            'position' => [
                'id' => $position['id'] ?? $employee->getPositionId(),
                'name' => $position['name'] ?? null,
            ],
            'division' => $employee->toDivisionSnapshot(),
            'branch' => $employee->toBranchSnapshot(),
        ]);
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
