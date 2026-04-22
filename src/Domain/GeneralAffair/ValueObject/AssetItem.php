<?php

declare(strict_types=1);

namespace App\Domain\GeneralAffair\ValueObject;

use App\Shared\Exception\BadRequestException;
use DateTimeImmutable;

final readonly class AssetItem
{
    public function __construct(
        public string $code,
        public float $price,
        public int $condition,
        public ?string $description,
        public DateTimeImmutable $buyingDate,
        public ?DateTimeImmutable $taxDate,
        public ?DateTimeImmutable $inspectionDate,
        public int $employeeId,
        public string $employeeName,
        public int $divisionId,
        public string $divisionName,
        public array $images = [],
        public array $changeLog = []
    ) {
        $this->validate();
    }

    public static function fromPayload(
        array $data,
        ?array $oldItemData,
        string $username,
        ?string $now,
        string $employeeName,
        string $divisionName
    ): self {
        $code = $data['code'] ?? throw new \App\Shared\Exception\BadRequestException('Code is required');

        $changeLog = self::buildChangeLog($oldItemData, $username, $now);

        return new self(
            code: $data['code'],
            price: (float) ($data['price'] ?? 0),
            condition: (int) ($data['condition'] ?? 0),
            description: $data['description'] ?? null,
            buyingDate: new \DateTimeImmutable($data['buying_date']),
            taxDate: !empty($data['tax_date']) ? new \DateTimeImmutable($data['tax_date']) : null,
            inspectionDate: !empty($data['inspection_date']) ? new \DateTimeImmutable($data['inspection_date']) : null,
            employeeId: (int) ($data['employee']['id'] ?? 0),
            employeeName: $employeeName,
            divisionId: (int) ($data['division']['id'] ?? 0),
            divisionName: $divisionName,
            images: $data['image'] ?? [],
            changeLog: $changeLog
        );
    }

    public static function fromStoredArray(array $data): self
    {
        return new self(
            code: $data['code'],
            price: (float) $data['price'],
            condition: (int) $data['condition'],
            description: $data['description'],
            buyingDate: new \DateTimeImmutable($data['buying_date']),
            taxDate: $data['tax_date'] ? new \DateTimeImmutable($data['tax_date']) : null,
            inspectionDate: $data['inspection_date'] ? new \DateTimeImmutable($data['inspection_date']) : null,
            employeeId: (int) $data['employee']['id'],
            employeeName: $data['employee']['name'],
            divisionId: (int) $data['division']['id'],
            divisionName: $data['division']['name'],
            images: $data['image'],
            changeLog: $data['items_change_log']
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'price' => $this->price,
            'condition' => $this->condition,
            'description' => $this->description,
            'buying_date' => $this->buyingDate->format('Y-m-d'),
            'tax_date' => $this->taxDate?->format('Y-m-d'),
            'inspection_date' => $this->inspectionDate?->format('Y-m-d'),
            'employee' => [
                'id' => $this->employeeId,
                'name' => $this->employeeName
            ],
            'division' => [
                'id' => $this->divisionId,
                'name' => $this->divisionName
            ],
            'image' => $this->images,
            'items_change_log' => $this->changeLog,
        ];
    }

    private static function buildChangeLog(?array $oldItemData, string $username, ?string $now): array
    {
        $changeLog = array_merge(self::defaultChangeLog(), $oldItemData['items_change_log'] ?? []);

        if ($oldItemData === null) {
            $changeLog['created_at'] = $now;
            $changeLog['created_by'] = $username;

            return $changeLog;
        }

        $changeLog['updated_at'] = $now;
        $changeLog['updated_by'] = $username;

        return $changeLog;
    }

    private static function defaultChangeLog(): array
    {
        return [
            'created_at' => null,
            'created_by' => null,
            'updated_at' => null,
            'updated_by' => null,
            'deleted_at' => null,
            'deleted_by' => null,
        ];
    }

    private function validate(): void
    {
        if (strlen($this->code) < 10 || strlen($this->code) > 25) {
            throw new BadRequestException('Code format invalid.');
        }
    }
}