<?php

declare(strict_types=1);

namespace App\Domain\PayrollReport\Entity;

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

final class PayrollReport
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'PayrollReport';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private int $attendanceSummaryId,
        private array $payrollSummary,
        private ?string $notes,
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
        int $attendanceSummaryId,
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
            attendanceSummaryId: $attendanceSummaryId,
            payrollSummary: [],
            notes: null,
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            lockVersion: LockVersion::create()
        );
    }

    public static function reconstitute(
        int $id,
        string $name,
        int $attendanceSummaryId,
        array $payrollSummary,
        ?string $notes,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ): self {

        return new self(
            id: $id,
            name: $name,
            attendanceSummaryId: $attendanceSummaryId,
            payrollSummary: $payrollSummary,
            notes: $notes,
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
            'attendance_summary_id' => $this->attendanceSummaryId,
            'payroll_summary' => $this->payrollSummary,
            'notes' => $this->notes,
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

    public function getAttendanceSummaryId(): int { return $this->attendanceSummaryId; }
    public function setAttendanceSummaryId(int $attendanceSummaryId): void { $this->attendanceSummaryId = $attendanceSummaryId; }

    public function getPayrollSummary(): array { return $this->payrollSummary; }
    public function setPayrollSummary(array $data): void { $this->payrollSummary = $data; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): void { $this->notes = $notes; }

    public function getSummaryType(): ?string
    {
        return $this->detailInfo->toArray()['summary_type'] ?? null;
    }
}
