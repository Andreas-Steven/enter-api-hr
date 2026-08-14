<?php

declare(strict_types=1);

namespace App\Application\AttendanceSummary\Dto;

final readonly class AttendanceSummaryListResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public ?int $period,
        public int $status,
        public array $employee,
        public array $summary,
        public array $detail_info,
    ) {}

    public static function fromRow(array $row): self
    {
        $data = AttendanceSummaryResponseMapper::fromListRow($row);

        return new self(
            id: $data['id'],
            name: $data['name'],
            period: $data['period'],
            status: $data['status'],
            employee: $data['employee'],
            summary: $data['summary'],
            detail_info: $data['detail_info'],
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
