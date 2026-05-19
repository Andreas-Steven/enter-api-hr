<?php

declare(strict_types=1);

namespace App\Domain\EmployeeLeave\Entity;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;

// Domain Layer
use App\Domain\Shared\Concerns\Entity\Stateful;
use App\Domain\Shared\Concerns\Entity\Identifiable;
use App\Domain\Shared\Concerns\Entity\Descriptive;

use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;

final class EmployeeLeave
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'EmployeeLeave';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private string $leaveType,
        private int $employeeId,
        private string $periodStartDate,
        private string $periodEndDate,
        private int $totalAmount,
        private ?int $adjustmentAmount,
        private ?int $usedAmount,
        private array $leaveRecords,
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
        string $leaveType,
        int $employeeId,
        string $periodStartDate,
        string $periodEndDate,
        int $totalAmount,
        ?int $adjustmentAmount,
        ?int $usedAmount,
        array $leaveRecords,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
    ): self {
        self::guardInitialStatus(
            status: $status,
            resource: self::RESOURCE
        );

        return new self(
            id: null, 
            name: $name, 
            leaveType: $leaveType,
            employeeId: $employeeId,
            periodStartDate: $periodStartDate,
            periodEndDate: $periodEndDate,
            totalAmount: $totalAmount,
            adjustmentAmount: $adjustmentAmount,
            usedAmount: $usedAmount,
            leaveRecords: $leaveRecords,
            status: $status, 
            detailInfo: $detailInfo, 
            syncMdb: $syncMdb, 
            lockVersion: LockVersion::create()
        );
    }

    public static function reconstitute(
        int $id,
        string $name,
        string $leaveType,
        int $employeeId,
        string $periodStartDate,
        string $periodEndDate,
        int $totalAmount,
        ?int $adjustmentAmount,
        ?int $usedAmount,
        array $leaveRecords,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ): self {

        return new self(
            id: $id, 
            name: $name, 
            leaveType: $leaveType,
            employeeId: $employeeId,
            periodStartDate: $periodStartDate,
            periodEndDate: $periodEndDate,
            totalAmount: $totalAmount,
            adjustmentAmount: $adjustmentAmount,
            usedAmount: $usedAmount,
            leaveRecords: $leaveRecords,
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
            'leave_type' => $this->leaveType,
            'employee_id' => $this->employeeId,
            'period_start_date' => $this->periodStartDate,
            'period_end_date' => $this->periodEndDate,
            'total_amount' => $this->totalAmount,
            'adjustment_amount' => $this->adjustmentAmount,
            'used_amount' => $this->usedAmount,
            'leave_records' => $this->leaveRecords,
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

    /**
     * Place EmployeeLeave-specific business functions here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     */ 
    public function getLeaveType(): string
    {
        return $this->leaveType;
    }

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function getPeriodStartDate(): string
    {
        return $this->periodStartDate;
    }

    public function getPeriodEndDate(): string
    {
        return $this->periodEndDate;
    }

    public function getTotalAmount(): int
    {
        return $this->totalAmount;
    }

    public function getAdjustmentAmount(): ?int
    {
        return $this->adjustmentAmount;
    }

    public function setLeaveType(string $leaveType): void
    {
        $this->leaveType = $leaveType;
    }

    public function updateLeaveType(?string $newLeaveType): void
    {
        if ($newLeaveType === null) {
            return;
        }

        $this->leaveType = $newLeaveType;
    }

    public function setEmployeeId(int $employeeId): void
    {
        $this->employeeId = $employeeId;
    }

    public function updateEmployeeId(?int $newEmployeeId): void
    {
        if ($newEmployeeId === null) {
            return;
        }

        $this->employeeId = $newEmployeeId;
    }

    public function setPeriodStartDate(string $periodStartDate): void
    {
        $this->periodStartDate = $periodStartDate;
    }

    public function updatePeriodStartDate(?string $newPeriodStartDate): void
    {
        if ($newPeriodStartDate === null) {
            return;
        }

        $this->periodStartDate = $newPeriodStartDate;
    }

    public function setPeriodEndDate(string $periodEndDate): void
    {
        $this->periodEndDate = $periodEndDate;
    }

    public function updatePeriodEndDate(?string $newPeriodEndDate): void
    {
        if ($newPeriodEndDate === null) {
            return;
        }

        $this->periodEndDate = $newPeriodEndDate;
    }

    public function setTotalAmount(int $totalAmount): void
    {
        $this->totalAmount = $totalAmount;
    }

    public function updateTotalAmount(?int $newTotalAmount): void
    {
        if ($newTotalAmount === null) {
            return;
        }

        $this->totalAmount = $newTotalAmount;
    }

    public function setAdjustmentAmount(?int $adjustmentAmount): void
    {
        $this->adjustmentAmount = $adjustmentAmount;
    }

    public function updateAdjustmentAmount(?int $newAdjustmentAmount): void
    {
        if ($newAdjustmentAmount === null) {
            return;
        }

        $this->adjustmentAmount = $newAdjustmentAmount;
    }
    
    public function getUsedAmount(): ?int
    {
        return $this->usedAmount;
    }

    public function setUsedAmount(?int $usedAmount): void
    {
        $this->usedAmount = $usedAmount;
    }
    
    public function updateUsedAmount(?int $newUsedAmount): void
    {
        if ($newUsedAmount === null) {
            return;
        }

        $this->setUsedAmount($newUsedAmount);
    }

    public function getLeaveRecords(): array
    {
        return $this->leaveRecords;
    }

    public function setLeaveRecords(array $leaveRecords): void
    {
        $this->leaveRecords = $leaveRecords;
    }

    public function updateLeaveRecords(?array $newLeaveRecords): void
    {
        if ($newLeaveRecords === null) {
            return;
        }

        $this->setLeaveRecords($newLeaveRecords);
    }
}