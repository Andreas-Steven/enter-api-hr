<?php

declare(strict_types=1);

namespace App\Domain\WorkTime\ValueObject;

use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

final readonly class WorkingDays
{
    /** * @var array<int, array{entry_time: ?string, home_time: ?string}> 
     */
    private array $days;

    public function __construct(array $workingDaysData)
    {
        $processed = [];

        // Loop wajib 1 sampai 7
        for ($index = 1; $index <= 7; $index++) {
            
            // 1. Pastikan index hari ada
            if (!isset($workingDaysData[$index])) {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'required',
                        params: ['attribute' => "working_days.{$index}"],
                        domain: 'validation',
                    )
                );
            }

            $dayData = $workingDaysData[$index];

            // 2. Pastikan isi per harinya adalah array
            if (!is_array($dayData)) {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'work_time.invalid_format',
                        params: [
                            'resource' => "working_days.{$index}",
                        ],
                        domain: 'app',
                    )
                );
            }

            // 3. Pastikan key entry_time dan home_time ada (wajib ada key-nya, walau value-nya null)
            if (!array_key_exists('entry_time', $dayData) || !array_key_exists('home_time', $dayData)) {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'array.must_contain',
                        params: [
                            'resource' => "working_days.{$index}", 
                            'field' => 'entry_time, home_time',
                        ],
                        domain: 'app',
                    )
                );
            }

            $processed[$index] = [
                'entry_time' => $dayData['entry_time'] !== null ? (string) $dayData['entry_time'] : null,
                'home_time'  => $dayData['home_time'] !== null ? (string) $dayData['home_time'] : null,
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
        if ($data === null) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'work_time.invalid_json',
                    params: [],
                    domain: 'app',
                )
            );
        }
        
        if (!is_array($data)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'work_time.invalid_format',
                    params: ['resource' => 'working_days'],
                    domain: 'app',
                )
            );
        }
        
        return new self($data);
    }

    public function getDay(int $dayIndex): ?array
    {
        return $this->days[$dayIndex] ?? null;
    }

    public function isOffDay(int $dayIndex): bool
    {
        $day = $this->getDay($dayIndex);
        return $day['entry_time'] === null && $day['home_time'] === null;
    }

    public function all(): array
    {
        return $this->days;
    }

    public function toArray(): array
    {
        return $this->days;
    }
}