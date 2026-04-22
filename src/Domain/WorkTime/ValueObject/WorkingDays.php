<?php

declare(strict_types=1);

namespace App\Domain\WorkTime\ValueObject;

use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

final readonly class WorkingDays
{
    /** * Secara internal tetap disimpan sebagai Map 1-7 agar performa lookup cepat
     * @var array<int, array{day_of_week: int, entry_time: ?string, home_time: ?string}> 
     */
    private array $days;

    public function __construct(array $workingDaysData)
    {
        $processed = [];
        
        // 1. Ubah list input menjadi map berdasarkan day_of_week untuk memudahkan validasi
        $inputMap = [];
        foreach ($workingDaysData as $item) {
            if (isset($item['day_of_week'])) {
                $inputMap[(int)$item['day_of_week']] = $item;
            }
        }

        // 2. Loop wajib 1 sampai 7 untuk memastikan integritas data seminggu penuh
        for ($index = 1; $index <= 7; $index++) {
            
            // Pastikan index hari ada dalam input
            if (!isset($inputMap[$index])) {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'required',
                        params: ['attribute' => "working_days.day_of_week.{$index}"],
                        domain: 'validation',
                    )
                );
            }

            $dayData = $inputMap[$index];

            // Pastikan key entry_time dan home_time ada
            if (!array_key_exists('entry_time', $dayData) || !array_key_exists('home_time', $dayData)) {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'array.must_contain',
                        params: [
                            'resource' => "working_days (day {$index})", 
                            'field' => 'entry_time, home_time',
                        ],
                        domain: 'app',
                    )
                );
            }

            $processed[$index] = [
                'day_of_week' => $index,
                'entry_time'  => $dayData['entry_time'] !== null ? (string) $dayData['entry_time'] : null,
                'home_time'   => $dayData['home_time'] !== null ? (string) $dayData['home_time'] : null,
            ];
        }

        $this->days = $processed;
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

    public function getDay(int $dayIndex): ?array
    {
        return $this->days[$dayIndex] ?? null;
    }

    public function isOffDay(int $dayIndex): bool
    {
        $day = $this->getDay($dayIndex);
        return $day && $day['entry_time'] === null && $day['home_time'] === null;
    }

    /**
     * Mengembalikan data dalam bentuk List (Array of Objects) 
     * sesuai dengan format JSON yang diinginkan.
     */
    public function toArray(): array
    {
        return array_values($this->days);
    }

    /**
     * Alias untuk toArray agar konsisten dengan VO lain
     */
    public function all(): array
    {
        return $this->toArray();
    }
}