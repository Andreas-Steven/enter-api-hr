<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebt\Dto;

use App\Domain\Shared\Enums\CreatesDebt;

final readonly class EmployeeDebtDetailInfo
{
    public function __construct(
        public ?array $employee,
        public ?array $deduction_type = null,
    ) {}

    public function toArray(): array
    {
        return [
            'employee' => $this->employee,
            'payroll_deduction_type' => $this->deduction_type,
        ];
    }

    public function getEmployee(): ?array
    {
        return $this->employee;
    }

    public function getDeductionType(): ?array
    {
        return $this->deduction_type;
    }

    public function createsDebt(): bool
    {
        $value = (int) ($this->deduction_type['creates_debt'] ?? 0);
        return CreatesDebt::from($value)->isYes();
    }
}