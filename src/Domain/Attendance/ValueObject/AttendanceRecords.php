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
                workPattern: $data['work_pattern'] ?? $data['division']['work_pattern'] ?? null,
                location: $data['location'] ?? null,
                branch: $data['branch'] ?? null,
                attendancePolicies: $data['attendance_policies'] ?? null,
                totalWorkMinutes: $data['total_work_minutes'] ?? null
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

    /**
     * Mencari satu record berdasarkan Employee ID
     */
    public function findByEmployeeId(int $employeeId): ?AttendanceItem
    {
        foreach ($this->items as $item) {
            if ($item->employeeId === $employeeId) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Mengganti item lama dengan yang baru berdasarkan employeeId
     */
    public function withUpsertItem(AttendanceItem $newItem): self
    {
        $items = $this->items; // Internal array of objects
        $found = false;

        foreach ($items as $index => $item) {
            if ($item->employeeId === $newItem->employeeId) {
                $items[$index] = $newItem;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $items[] = $newItem;
        }

        return new self($items);
    }

    /**
     * Memicu evaluasi status untuk semua item (Late, Leave Early, dll)
     */
    public function withEvaluatedStatus(string $date): self
    {
        $evaluatedItems = array_map(
            fn(AttendanceItem $item) => $item->withEvaluatedStatus($date),
            $this->items
        );

        return new self($evaluatedItems);
    }
}