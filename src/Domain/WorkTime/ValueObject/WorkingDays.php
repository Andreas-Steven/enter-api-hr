<?php

declare(strict_types=1);

namespace App\Domain\WorkTime\ValueObject;

use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

use DateTime;

final readonly class WorkingDays
{
    private array $days;

    public function __construct(array $workingDaysData)
    {
        // Pindahkan semua keribetan ke method private
        $inputMap = $this->mapAndValidateInput($workingDaysData);
        
        $processed = [];

        for ($index = 1; $index <= 7; $index++) {
            $dayData = $this->getDayDataOrThrow($inputMap, $index);
            $dayName = self::getDayName($index);

            $entryTime = self::normalizeTime($dayData['entry_time'], 'Entry Time', $dayName);
            $homeTime = self::normalizeTime($dayData['home_time'], 'Home Time', $dayName);

            $processed[$index] = [
                'day_of_week' => $index,
                'entry_time'  => $entryTime,
                'home_time'   => $homeTime,
            ];
        }

        $this->days = $processed;
    }

    private static function getDayName(int $dayOfWeek): string
    {
        return match ($dayOfWeek) {
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
            default => (string) $dayOfWeek,
        };
    }

    private function mapAndValidateInput(array $data): array
    {
        $map = [];
        foreach ($data as $item) {
            if (!is_array($item) || !isset($item['day_of_week'])) {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'work_time.invalid_format', 
                        params: ['resource' => 'Working Days'], 
                        domain: 'app'
                    )
                );
            }
            $map[(int)$item['day_of_week']] = $item;
        }
        return $map;
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

        $dayData = $map[$index];
        if (!array_key_exists('entry_time', $dayData) || !array_key_exists('home_time', $dayData)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'array.must_contain', 
                    params: [
                        'resource' => "working_days (day {$index})", 
                        'field' => 'entry_time, home_time'
                    ], 
                    domain: 'app'
                )
            );
        }

        return $dayData;
    }

    private static function assertValidTime(?string $time, string $attribute, string $day): void
    {
        self::normalizeTime($time, $attribute, $day);
    }

    private static function normalizeTime(?string $time, string $attribute, string $day): ?string
    {
        if ($time === null || $time == '') {
            return null;
        }

        $dateTime = DateTime::createFromFormat('H:i:s', $time);

        if ($dateTime && $dateTime->format('H:i:s') === $time) {
            return $time;
        }

        $dateTime = DateTime::createFromFormat('H:i', $time);

        if ($dateTime && $dateTime->format('H:i') === $time) {
            return $dateTime->format('H:i:s');
        }

        {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'work_time.invalid_format',
                    params: [
                        'resource' => $attribute,
                        'day' => $day,
                    ],
                    domain: 'app',
                )
            );
        }

        return null;
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        if ($data === null || !is_array($data)) {
             throw new BadRequestException(
                translate: Message::create(
                    key: 'work_time.invalid_format',
                    params: ['resource' => 'working_days'],
                    domain: 'app',
                )
            );
        }
        
        // Jika JSON mengandung key 'working_days', ambil isinya
        $list = $data['working_days'] ?? $data;
        
        return new self($list);
    }

    public function toArray(): array
    {
        return array_values($this->days);
    }

    public function getDay(int $dayIndex): ?array
    {
        return $this->days[$dayIndex] ?? null;
    }

    public function isOffDay(int $dayIndex): bool
    {
        $day = $this->getDay($dayIndex);
        return $day && $day['entry_time'] === null && $day['home_time'] === null;
    }
}