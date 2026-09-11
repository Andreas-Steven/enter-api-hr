<?php

declare(strict_types=1);

namespace App\Application\PayrollRatePolicies;

use App\Application\PayrollRatePolicies\Dto\PayrollRatePoliciesDetailInfo;
use App\Domain\PayrollDeductionType\Repository\PayrollDeductionTypeRepositoryInterface;
use App\Shared\Enums\RecordStatus;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * Membangun PayrollRatePoliciesDetailInfo dari deduction_type_id.
 *
 * Untuk deduction incidents: lookup relasi via repository, throw
 * NotFoundException bila relasi tidak ditemukan.
 * Untuk earning incidents (OVERTIME): deduction_type_id null, return DTO
 * dengan deduction_type null.
 */
final readonly class PayrollRatePoliciesDetailInfoFactory
{
    public function __construct(
        private PayrollDeductionTypeRepositoryInterface $payrollDeductionTypeRepository,
    ) {}

    public function buildFromDeductionTypeId(?int $deductionTypeId): PayrollRatePoliciesDetailInfo
    {
        if ($deductionTypeId === null) {
            return new PayrollRatePoliciesDetailInfo(
                deductionType: null,
            );
        }

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

        return new PayrollRatePoliciesDetailInfo(
            deductionType: $deductionType->toPersistence(),
        );
    }

    public function buildFromPrimitives(?array $deductionType = null): PayrollRatePoliciesDetailInfo
    {
        return new PayrollRatePoliciesDetailInfo(
            deductionType: $deductionType,
        );
    }
}
