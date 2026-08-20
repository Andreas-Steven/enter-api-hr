<?php

declare(strict_types=1);

namespace App\Domain\PayrollRatePolicies\Entity;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;

// Domain Layer
use App\Domain\Shared\Concerns\Entity\Stateful;
use App\Domain\Shared\Concerns\Entity\Identifiable;
use App\Domain\Shared\Concerns\Entity\Descriptive;

use App\Domain\Shared\Enums\PayrollRateType;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;

/**
 * PayrollRatePolicies Entity
 *
 * Global configuration that maps an incident type to an amount:
 * - Deduction incidents (LATE, EARLY_OUT, MISSING_IN, MISSING_OUT, ABSENT):
 *   deduction_type_id is set and must point to a PayrollDeductionType with
 *   creates_debt = 0.
 * - Earning incidents (OVERTIME): deduction_type_id is null (overtime is an
 *   earning, not a deduction).
 *
 * One row per incident type. Used by PayrollReportDomainService when
 * auto-generating PayrollDeduction records and overtime earnings from
 * Attendance data.
 */
final class PayrollRatePolicies
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'PayrollRatePolicies';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private PayrollRateType $rateType,
        private ?int $deductionTypeId,
        private int $amount,
        private ResourceStatus $status,
        private DetailInfo $detailInfo,
        private ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ) {
        $this->resource = self::RESOURCE;
        $this->lockVersion = $lockVersion ?? LockVersion::create();
    }

    public static function getResource(): string
    {
        return self::RESOURCE;
    }

    public static function create(
        string $name,
        PayrollRateType $rateType,
        ?int $deductionTypeId,
        int $amount,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
    ): self {
        self::guardInitialStatus(
            status: $status,
            resource: self::RESOURCE
        );

        if ($amount < 0) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'validation.min_value',
                    domain: 'validation',
                    params: ['field' => 'amount', 'min' => 0]
                )
            );
        }

        return new self(
            id: null,
            name: $name,
            rateType: $rateType,
            deductionTypeId: $deductionTypeId,
            amount: $amount,
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            lockVersion: LockVersion::create()
        );
    }

    public static function reconstitute(
        int $id,
        string $name,
        PayrollRateType $rateType,
        ?int $deductionTypeId,
        int $amount,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            rateType: $rateType,
            deductionTypeId: $deductionTypeId,
            amount: $amount,
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            lockVersion: $lockVersion ?? LockVersion::create()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'rate_type' => $this->rateType->value,
            'deduction_type_id' => $this->deductionTypeId,
            'amount' => $this->amount,
            'status' => $this->status->value(),
            'detail_info' => $this->detailInfo->toArray(),
            SyncMdb::field() => $this->syncMdb?->value(),
            LockVersion::field() => $this->lockVersion->value(),
        ];
    }

    public function restore(): void
    {
        if (!$this->status->isDeleted()) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'resource.not_deleted',
                    params: ['id' => $this->id]
                )
            );
        }

        $this->status = ResourceStatus::restored();
    }

    public function getRateType(): PayrollRateType
    {
        return $this->rateType;
    }

    public function getDeductionTypeId(): ?int
    {
        return $this->deductionTypeId;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function updateAmount(int $newAmount): void
    {
        if ($newAmount < 0) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'validation.min_value',
                    domain: 'validation',
                    params: ['field' => 'amount', 'min' => 0]
                )
            );
        }

        if ($this->amount === $newAmount) {
            return;
        }

        $this->amount = $newAmount;
    }

    public function updateDeductionTypeId(?int $newDeductionTypeId): void
    {
        if ($newDeductionTypeId !== null && $newDeductionTypeId < 1) {
            return;
        }

        if ($this->deductionTypeId === $newDeductionTypeId) {
            return;
        }

        $this->deductionTypeId = $newDeductionTypeId;
    }

    public function updateRateType(PayrollRateType $newRateType): void
    {
        if ($this->rateType === $newRateType) {
            return;
        }

        $this->rateType = $newRateType;
    }
}
