<?php

declare(strict_types=1);

namespace App\Application\AttendanceFineRate\Dto;

/**
 * DTO ringkas untuk disimpan di kolom detail_info JSON AttendanceFineRate.
 *
 * Berisi snapshot deduction_type agar tidak perlu join saat baca data.
 */
final readonly class AttendanceFineRateDetailInfo
{
    public function __construct(
        public ?array $deductionType,
    ) {}

    public function toArray(): array
    {
        return [
            'deduction_type' => $this->deductionType,
        ];
    }

    public function getDeductionType(): ?array
    {
        return $this->deductionType;
    }
}
