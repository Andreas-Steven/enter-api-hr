<?php

declare(strict_types=1);

namespace App\Domain\WorkPattern\ValueObject;

use App\Domain\AttendanceShift\Entity\AttendanceShift;
use App\Domain\AttendanceShift\Repository\AttendanceShiftRepositoryInterface;
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

final readonly class WorkingDays
{
    private array $days;

    public function __construct(
        array $workingDaysData,
        private AttendanceShiftRepositoryInterface $attendanceShiftRepository
    )
    {
        $inputMap = $this->mapAndValidateInput($workingDaysData);

        $processed = [];

        for ($index = 1; $index <= 7; $index++) {
            $dayData = $this->getDayDataOrThrow($inputMap, $index);

            $shift = $this->normalizeShiftId($dayData['shift_id'] ?? null);

            $processed[$index] = [
                'day_of_week' => $index,
                'shift' => $shift,
            ];
        }

        $this->days = $processed;
    }

    private function mapAndValidateInput(array $data): array
    {
        $map = [];
        foreach ($data as $item) {
            if (!is_array($item) || !isset($item['day_of_week'])) {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'work_pattern.invalid_format',
                        params: ['resource' => 'Working Days'],
                        domain: 'app'
                    )
                );
            }
            $dayOfWeek = (int)$item['day_of_week'];

            if (!array_key_exists('shift_id', $item)) {
                $item['shift_id'] = $this->extractShiftIdFromLegacyData($item['shift'] ?? null);
            }

            $map[$dayOfWeek] = $item;
        }
        return $map;
    }

    private function extractShiftIdFromLegacyData(mixed $shift): ?int
    {
        if ($shift instanceof AttendanceShift) {
            return $shift->getId();
        }

        if (is_array($shift) && array_key_exists('id', $shift) && is_numeric($shift['id'])) {
            $shiftId = (int)$shift['id'];
            return $shiftId > 0 ? $shiftId : null;
        }

        if ($shift !== null && !is_array($shift) && is_numeric($shift)) {
            $shiftId = (int)$shift;
            return $shiftId > 0 ? $shiftId : null;
        }

        return null;
    }

    private function getDayDataOrThrow(array $map, int $index): array
    {
        if (!isset($map[$index])) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'required',
                    params: ['attribute' => "working_days.day_of_week.{$index}"],
                    domain: 'validation'
                )
            );
        }

        return $map[$index];
    }

    private function normalizeShiftId(mixed $shiftId): ?AttendanceShift
    {
        if ($shiftId === null || $shiftId === '') {
            return null;
        }

        if (!is_numeric($shiftId) || (int)$shiftId <= 0) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'work_pattern.invalid_shift_id',
                    params: [],
                    domain: 'app'
                )
            );
        }

        $shiftIdInt = (int)$shiftId;

        $shift = $this->attendanceShiftRepository->findById($shiftIdInt);
        if ($shift === null) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'work_pattern.shift_not_found',
                    params: ['id' => $shiftIdInt],
                    domain: 'app'
                )
            );
        }

        return $shift;
    }

    public static function fromArray(array $data, AttendanceShiftRepositoryInterface $attendanceShiftRepository): self
    {
        return new self($data, $attendanceShiftRepository);
    }

    public static function fromJson(string $json, AttendanceShiftRepositoryInterface $attendanceShiftRepository): self
    {
        $data = json_decode($json, true);
        if ($data === null || !is_array($data)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'work_pattern.invalid_format',
                    params: ['resource' => 'working_days'],
                    domain: 'app',
                )
            );
        }

        $list = $data['working_days'] ?? $data;

        return new self($list, $attendanceShiftRepository);
    }

    public function toArray(): array
    {
        $result = [];
        foreach ($this->days as $day) {
            $shift = $day['shift'];
            $result[] = [
                'day_of_week' => $day['day_of_week'],
                'shift' => $shift ? [
                    'id' => $shift->getId(),
                    'name' => $shift->getName(),
                    'start_time' => $shift->getStartTime(),
                    'end_time' => $shift->getEndTime(),
                ] : null,
            ];
        }
        return $result;
    }

    public function getDay(int $dayIndex): ?array
    {
        return $this->days[$dayIndex] ?? null;
    }

    public function getShiftId(int $dayIndex): ?int
    {
        $day = $this->getDay($dayIndex);
        $shift = $day['shift'] ?? null;
        return $shift?->getId();
    }

    public function isOffDay(int $dayIndex): bool
    {
        $day = $this->getDay($dayIndex);
        return $day && $day['shift'] === null;
    }
}