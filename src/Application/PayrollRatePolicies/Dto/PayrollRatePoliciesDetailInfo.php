<?php

declare(strict_types=1);

namespace App\Application\PayrollRatePolicies\Dto;

/**
 * DTO ringkas untuk disimpan di kolom detail_info JSON PayrollRatePolicies.
 *
 * Berisi snapshot deduction_type agar tidak perlu join saat baca data.
 * Untuk earning incidents (OVERTIME), deduction_type akan null.
 */
final readonly class PayrollRatePoliciesDetailInfo
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
