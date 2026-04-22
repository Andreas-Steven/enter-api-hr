<?php

declare(strict_types=1);

namespace App\Domain\AttendancePermission\ValueObject;

use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;

use DateTimeImmutable;

final class Revision
{
    private ?string $startTime;
    private ?string $endTime;

    public function __construct(
        ?string $startTime, 
        ?string $endTime
    ) {
        self::validateTimeFormat($startTime, 'Start Time');
        self::validateTimeFormat($endTime, 'End Time');

        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    /**
     * Validate time format
     */
    private static function validateTimeFormat(?string $time, string $label): void
    {
        if (empty($time)) {
            return;
        }

        $dt = DateTimeImmutable::createFromFormat('H:i:s', $time);
        if (!$dt || $dt->format('H:i:s') !== $time) {
             throw new BadRequestException(
                translate: Message::create(
                    key: 'date.format',
                    params: [
                        'field' => $label,
                        'format' => 'H:i:s'
                    ],
                    domain: 'validation',
                )
            );
        }
    }
    
    public function equals(Revision $other): bool
    {
        return $this->startTime === $other->startTime && $this->endTime === $other->endTime;
    }
    
    /**
     * Checking the revision is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->startTime) && empty($this->endTime);
    }
    
    public function hasStartTime(): bool
    {
        return $this->startTime !== null;
    }

    public function hasEndTime(): bool
    {
        return $this->endTime !== null;
    }

    /**
     * Get start time
     */
    public function getStartTime(): ?string
    {
        return $this->startTime;
    }

    public function getEndTime(): ?string
    {
        return $this->endTime;
    }

    /**
     * Revision data transformation
     */
    public static function fromArray(?array $data): ?self
    {
        if (empty($data) || (empty($data['start_time']) && empty($data['end_time']))) {
            return null;
        }

        return new self(
            $data['start_time'] ?? null,
            $data['end_time'] ?? null
        );
    }

    public static function fromJson(?string $jsonString): ?self
    {
        if (empty($jsonString)) {
            return null;
        }
        
        $data = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
            return null;
        }
        
        return self::fromArray($data);
    }

    public function toArray(): array
    {
        return [
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
        ];
    }
}