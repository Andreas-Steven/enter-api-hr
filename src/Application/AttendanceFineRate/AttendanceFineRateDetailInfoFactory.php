<?php

declare(strict_types=1);

namespace App\Application\AttendanceFineRate;

use App\Application\AttendanceFineRate\Dto\AttendanceFineRateDetailInfo;
use App\Domain\PayrollDeductionType\Repository\PayrollDeductionTypeRepositoryInterface;
use App\Shared\Enums\RecordStatus;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * Membangun AttendanceFineRateDetailInfo dari deduction_type_id.
 *
 * Mengikuti pola PayrollDeductionDetailInfoFactory: lookup relasi via repository,
 * throw NotFoundException bila relasi tidak ditemukan, kembalikan DTO ringkas
 * untuk disimpan di kolom detail_info JSON.
 */
final readonly class AttendanceFineRateDetailInfoFactory
{
    public function __construct(
        private PayrollDeductionTypeRepositoryInterface $payrollDeductionTypeRepository,
    ) {}

    public function buildFromDeductionTypeId(int $deductionTypeId): AttendanceFineRateDetailInfo
    {
        $deductionType = $this->payrollDeductionTypeRepository->findById(
            id: $deductionTypeId,
            status: RecordStatus::ACTIVE->value
        );

        if ($deductionType === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => 'PayrollDeductionType',
                        'field' => 'id',
                        'value' => $deductionTypeId,
                    ]
                )
            );
        }

        return new AttendanceFineRateDetailInfo(
            deductionType: [
                'id' => $deductionType->getId(),
                'name' => $deductionType->getName(),
                'category' => $deductionType->getCategory(),
                'creates_debt' => $deductionType->getCreatesDebt(),
            ],
        );
    }

    public function buildFromPrimitives(?array $deductionType = null): AttendanceFineRateDetailInfo
    {
        return new AttendanceFineRateDetailInfo(
            deductionType: $deductionType,
        );
    }
}
