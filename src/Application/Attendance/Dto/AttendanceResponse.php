<?php

declare(strict_types=1);

namespace App\Application\Attendance\Dto;

use App\Domain\Attendance\Entity\Attendance;

final readonly class AttendanceResponse
{
    public function __construct(
        public int $id,
        public string $date,
        public int $period,
        public array $attendance,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(Attendance $entity, array $workPatternMap = []): self
    {
        return new self(
            id: $entity->getId(),
            date: $entity->getDate(),
            period: $entity->getPeriod(),
            attendance: array_map(
                fn(array $item): array => self::sanitizeAttendanceItem($item, $entity->getDate(), $workPatternMap),
                $entity->getAttendance()
            ),
            status: $entity->getStatus()->value(),
            detail_info: $entity->getDetailInfo()->toArray(),
            sync_mdb: $entity->getSyncMdbValue(),
            lock_version: $entity->getLockVersion()->value(),
        );
    }

    /**
     * Re-enrich an attendance item with the current WorkPattern snapshot so
     * that historical records reflect the latest work_pattern_rule even though
     * they were stored with an older work_pattern snapshot.
     *
     * @param array<int, array> $workPatternMap work_pattern_id => work_pattern array
     */
    public static function enrichAttendanceItem(array $item, string $date, array $workPatternMap): array
    {
        $workPattern = $item['work_pattern'] ?? [];
        $wpId = (int)($workPattern['id'] ?? 0);

        if ($wpId <= 0 || !isset($workPatternMap[$wpId])) {
            return $item;
        }

        $currentWorkPattern = $workPatternMap[$wpId];
        $dayOfWeek = (int) date('N', strtotime($date));
        $currentDay = null;
        foreach ($currentWorkPattern['working_days'] ?? [] as $day) {
            if ((int)($day['day_of_week'] ?? 0) === $dayOfWeek) {
                $currentDay = $day;
                break;
            }
        }

        if ($currentDay === null) {
            return $item;
        }

        $item['work_pattern'] = [
            'id' => $currentWorkPattern['id'] ?? $workPattern['id'] ?? null,
            'name' => $currentWorkPattern['name'] ?? $workPattern['name'] ?? null,
            'working_days' => [$currentDay],
        ];

        return $item;
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private static function sanitizeAttendanceItem(array $item, string $date, array $workPatternMap): array
    {
        unset($item['attendance_policies']);

        return self::enrichAttendanceItem($item, $date, $workPatternMap);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}