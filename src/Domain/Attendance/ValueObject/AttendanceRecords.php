<?php

declare(strict_types=1);

namespace App\Domain\Attendance\ValueObject;

final readonly class AttendanceRecords
{
    /** @var AttendanceItem[] */
    private array $items;

    private function __construct(array $items)
    {
        $this->items = $items;
    }

    public static function fromArray(array $rawItems): self
    {
        $items = array_map(function (array $data) {
            return new AttendanceItem(
                employeeId: (int) $data['employee_id'],
                employeeName: $data['employee_name'],
                info: $data['info'],
                device: $data['device'],
                division: $data['division'],
                startTime: $data['start_time'] ?? null,
                endTime: $data['end_time'] ?? null,
                workTime: $data['work_time'] ?? null,
                location: $data['location'] ?? null
            );
        }, $rawItems);

        return new self($items);
    }

    /**
     * Mengambil daftar item asli (sebagai indexed array)
     */
    public function getItems(): array
    {
        return array_map(fn(AttendanceItem $item) => $item->toArray(), $this->items);
    }

    /**
     * Factory untuk membuat instance dari array of AttendanceItem (Objek)
     * @param AttendanceItem[] $items
     */
    public static function fromItems(array $items): self
    {
        return new self($items);
    }

    /**
     * Mengambil Metadata terpisah (Search Map & Stats)
     */
    public function getMeta(): array
    {
        $lookupIndex = [];
        $lateCount = 0;

        foreach ($this->items as $index => $item) {
            // Memberikan lookup index untuk search O(1)
            $lookupIndex[(string)$item->employeeId] = $index;
            
            if ($item->isLate()) {
                $lateCount++;
            }
        }

        return [
            'total_count' => count($this->items),
            'late_count' => $lateCount,
            'lookup_index' => $lookupIndex,
        ];
    }
}