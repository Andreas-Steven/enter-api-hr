<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReport\Entity;

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
use App\Domain\AttendanceReport\ValueObject\Summary;

final class AttendanceReport
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'AttendanceReport';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private int $period,
        private int $employeeId,
        private int $branchId,
        private int $divisionId,
        private int $positionId,
        private int $employeeTypeId,
        private int $workPatternId,
        private Summary $summary,
        private string $reportType,
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
        int $period,
        int $employeeId,
        int $branchId,
        int $divisionId,
        int $positionId,
        int $employeeTypeId,
        int $workPatternId,
        Summary $summary,
        string $reportType,
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
            period: $period,
            employeeId: $employeeId,
            branchId: $branchId,
            divisionId: $divisionId,
            positionId: $positionId,
            employeeTypeId: $employeeTypeId,
            workPatternId: $workPatternId,
            summary: $summary,
            reportType: $reportType,
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            lockVersion: LockVersion::create()
        );
    }

    public static function reconstitute(
        int $id,
        string $name,
        int $period,
        int $employeeId,
        int $branchId,
        int $divisionId,
        int $positionId,
        int $employeeTypeId,
        int $workPatternId,
        Summary $summary,
        string $reportType,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            period: $period,
            employeeId: $employeeId,
            branchId: $branchId,
            divisionId: $divisionId,
            positionId: $positionId,
            employeeTypeId: $employeeTypeId,
            workPatternId: $workPatternId,
            summary: $summary,
            reportType: $reportType,
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
            'period' => $this->period,
            'employee_id' => $this->employeeId,
            'branch_id' => $this->branchId,
            'division_id' => $this->divisionId,
            'position_id' => $this->positionId,
            'employee_type_id' => $this->employeeTypeId,
            'work_pattern_id' => $this->workPatternId,
            'summary' => $this->summary->toArray(),
            'report_type' => $this->reportType,
            'status' => $this->status->value(),
            'detail_info' => $this->detailInfo->toArray(),
            SyncMdb::field() => $this->syncMdb?->value(),
            LockVersion::field() => $this->lockVersion->value(),
        ];
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function getBranchId(): int
    {
        return $this->branchId;
    }

    public function getDivisionId(): int
    {
        return $this->divisionId;
    }

    public function getPositionId(): int
    {
        return $this->positionId;
    }

    public function getEmployeeTypeId(): int
    {
        return $this->employeeTypeId;
    }

    public function getWorkPatternId(): int
    {
        return $this->workPatternId;
    }

    public function getSummary(): Summary
    {
        return $this->summary;
    }

    public function getReportType(): string
    {
        return $this->reportType;
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
}
