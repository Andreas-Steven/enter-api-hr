<?php

declare(strict_types=1);

namespace App\Application\EmployeeLeave\Dto;

/**
 * Detail info payload for an EmployeeLeave record.
 *
 * Carries the employee snapshot (id, name, type, position, division, branch)
 * captured at assignment time so the leave record stays readable even if the
 * employee master data changes afterwards.
 */
final readonly class EmployeeLeaveDetailInfo
{
    public function __construct(
        public ?array $employee,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'employee' => $this->employee,
        ];
    }

    /** @return array<string, mixed>|null */
    public function getEmployee(): ?array
    {
        return $this->employee;
    }
}
