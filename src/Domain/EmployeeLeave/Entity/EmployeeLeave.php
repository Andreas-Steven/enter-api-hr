<?php

declare(strict_types=1);

namespace App\Domain\EmployeeLeave\Entity;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\ConflictException;

// Domain Layer
use App\Domain\Shared\Concerns\Entity\Stateful;
use App\Domain\Shared\Concerns\Entity\Identifiable;
use App\Domain\Shared\Concerns\Entity\Descriptive;
use App\Domain\Shared\Enums\AdjustmentType;
use App\Domain\Shared\Enums\AdjustmentReason;

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
        private int $initialAmount,
        private ?int $usedAmount,
        private array $leaveRecords,
        private array $adjustmentRecords,
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
        int $initialAmount,
        ?int $usedAmount,
        array $leaveRecords,
        array $adjustmentRecords,
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
            initialAmount: $initialAmount,
            usedAmount: $usedAmount,
            leaveRecords: $leaveRecords,
            adjustmentRecords: $adjustmentRecords,
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
        int $initialAmount,
        ?int $usedAmount,
        array $leaveRecords,
        array $adjustmentRecords,
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
            initialAmount: $initialAmount,
            usedAmount: $usedAmount,
            leaveRecords: $leaveRecords,
            adjustmentRecords: $adjustmentRecords,
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
            'initial_amount' => $this->initialAmount,
            'adjustment_amount' => $this->getAdjustmentAmount(),
            'used_amount' => $this->usedAmount,
            'remaining_amount' => $this->getRemainingAmount(),
            'leave_records' => $this->leaveRecords,
            'adjustment_records' => $this->adjustmentRecords,
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

    public function getInitialAmount(): int
    {
        return $this->initialAmount;
    }

    /**
     * Returns the signed sum of all adjustment_records.
     *
     * Derived from adjustment_records — not stored separately — so it is
     * always consistent with the transaction history.
     */
    public function getAdjustmentAmount(): int
    {
        $total = 0;
        foreach ($this->adjustmentRecords as $record) {
            $total += $record['adjustment_type'] === 'minus'
                ? -$record['amount']
                : $record['amount'];
        }
        return $total;
    }

    /**
     * Returns the remaining leave quota available for use.
     *
     * Formula: initial_amount + adjustment_amount - used_amount.
     */
    public function getRemainingAmount(): ?int
    {
        if ($this->usedAmount === null) {
            return $this->initialAmount + $this->getAdjustmentAmount();
        }

        return $this->initialAmount + $this->getAdjustmentAmount() - $this->usedAmount;
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

    public function setInitialAmount(int $initialAmount): void
    {
        $this->initialAmount = $initialAmount;
    }

    public function updateInitialAmount(?int $newInitialAmount): void
    {
        if ($newInitialAmount === null) {
            return;
        }

        $this->initialAmount = $newInitialAmount;
    }

    /**
     * Returns the full history of adjustment transactions.
     *
     * @return list<array<string, mixed>>
     */
    public function getAdjustmentRecords(): array
    {
        return $this->adjustmentRecords;
    }

    public function setAdjustmentRecords(array $adjustmentRecords): void
    {
        $this->adjustmentRecords = $adjustmentRecords;
    }

    /**
     * Appends a new adjustment transaction to adjustment_records and
     * recalculates adjustment_amount as the sum of all records.
     *
     * @param int $amount           absolute amount (always positive)
     * @param AdjustmentType $type  direction: PLUS (add) or MINUS (subtract)
     * @param AdjustmentReason $reason  reason category (REWARD, MANUAL, etc)
     * @param string|null $note     free-form description (e.g. event name)
     */
    public function addAdjustment(
        int $amount,
        AdjustmentType $type,
        AdjustmentReason $reason,
        ?string $note = null,
    ): void {
        $signedAmount = $type === AdjustmentType::MINUS ? -$amount : $amount;

        $this->adjustmentRecords[] = [
            'amount' => $amount,
            'adjustment_type' => $type->value,
            'leave_type' => $reason->value,
            'note' => $note,
        ];
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

    /**
     * Appends a leave_record linked to an AttendancePermission and
     * recalculates used_amount from the full record history.
     *
     * A record with status "approved" consumes quota; a record with status
     * "rejected" reverses a previously-approved record (kept as history so
     * the audit trail stays intact). used_amount is always derived as:
     *
     *   sum(total_day where status = approved)
     *   - sum(total_day where status = rejected)
     *
     * Quota guard: when adding an "approved" record, the remaining quota
     * (initial_amount + adjustment_amount - used_amount) must be >= total_day.
     * Otherwise a ConflictException is thrown so the caller (approval flow)
     * can abort before persisting anything.
     *
     * @param int         $permissionId  AttendancePermission id that triggered this record
     * @param string      $startDate     leave start date (Y-m-d)
     * @param string|null $endDate       leave end date (Y-m-d), null for single day
     * @param int         $totalDay      number of days consumed (always positive)
     * @param string      $status        "approved" or "rejected"
     * @param string|null $note          optional note (e.g. approved_note / reject reason)
     */
    public function addLeaveRecord(
        int $permissionId,
        string $startDate,
        ?string $endDate,
        int $totalDay,
        string $status,
        ?string $note = null,
    ): void {
        if ($totalDay < 1) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'employee_leave.invalid_total_day',
                    params: ['total_day' => $totalDay],
                    domain: 'app',
                )
            );
        }

        // Quota guard only applies to consuming (approved) records.
        if ($status === 'approved') {
            $remaining = $this->getRemainingAmount() ?? 0;
            if ($remaining < $totalDay) {
                throw new ConflictException(
                    translate: Message::create(
                        key: 'employee_leave.quota_exceeded',
                        params: [
                            'remaining' => $remaining,
                            'requested' => $totalDay,
                            'permission_id' => $permissionId,
                        ],
                        domain: 'app',
                    )
                );
            }
        }

        $this->leaveRecords[] = [
            'permission_id' => $permissionId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_day' => $totalDay,
            'status' => $status,
            'note' => $note,
        ];

        $this->recalculateUsedAmount();
    }

    /**
     * Recomputes used_amount from the leave_records history.
     *
     * used_amount = sum(total_day where status = approved)
     *             - sum(total_day where status = rejected)
     */
    public function recalculateUsedAmount(): void
    {
        $used = 0;
        foreach ($this->leaveRecords as $record) {
            $recordStatus = $record['status'] ?? null;
            $recordDay = (int) ($record['total_day'] ?? 0);

            if ($recordStatus === 'approved') {
                $used += $recordDay;
            } elseif ($recordStatus === 'rejected') {
                $used -= $recordDay;
            }
        }

        $this->usedAmount = $used;
    }
}