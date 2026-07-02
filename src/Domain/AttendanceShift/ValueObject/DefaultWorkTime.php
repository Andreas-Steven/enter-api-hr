<?php

declare(strict_types=1);

namespace App\Domain\AttendanceShift\ValueObject;

// Shared Layer
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

/**
 * Default Work Time Value Object
 * 
 * Calculates and stores the default work time in minutes based on start_time and end_time.
 * Handles cross-day shifts (e.g., 22:00 to 06:00).
 */
final readonly class DefaultWorkTime
{
    private int $value;

    public function __construct(int $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    /**
     * Create DefaultWorkTime from start and end time strings
     * 
     * @param string $startTime Format: HH:MM:SS
     * @param string $endTime Format: HH:MM:SS
     * @param int $breakMinutes Break time in minutes to subtract
     * @param int $crossDay 0 if same day, 1 if crosses to next day
     */
    public static function fromTimeRange(
        string $startTime,
        string $endTime,
        int $breakMinutes = 0,
        int $crossDay = 0
    ): self {
        $startParts = explode(':', $startTime);
        $endParts = explode(':', $endTime);

        if (count($startParts) < 2 || count($endParts) < 2) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'invalid_time_format',
                    params: []
                )
            );
        }

        $startHour = (int) $startParts[0];
        $startMinute = (int) $startParts[1];
        $endHour = (int) $endParts[0];
        $endMinute = (int) $endParts[1];

        // Convert to minutes
        $startTotalMinutes = $startHour * 60 + $startMinute;
        $endTotalMinutes = $endHour * 60 + $endMinute;

        // Calculate work time
        if ($crossDay === 1) {
            // Cross day: add 24 hours (1440 minutes) to end time
            $workMinutes = ($endTotalMinutes + 1440) - $startTotalMinutes;
        } else {
            // Same day
            $workMinutes = $endTotalMinutes - $startTotalMinutes;
        }

        // Subtract break time
        $workMinutes -= $breakMinutes;

        // Ensure non-negative
        if ($workMinutes < 0) {
            $workMinutes = 0;
        }

        return new self($workMinutes);
    }

    /**
     * Create from integer value
     */
    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    /**
     * Get value in minutes
     */
    public function value(): int
    {
        return $this->value;
    }

    /**
     * Get value in hours (decimal)
     */
    public function toHours(): float
    {
        return round($this->value / 60, 2);
    }

    /**
     * Get formatted time string (HH:MM)
     */
    public function toFormatted(): string
    {
        $hours = intdiv($this->value, 60);
        $minutes = $this->value % 60;
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * Validate work time value
     */
    private function validate(int $value): void
    {
        if ($value < 0) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'default_work_time.invalid_negative',
                    params: ['value' => $value]
                )
            );
        }

        if ($value > 1440) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'default_work_time.exceeds_24h',
                    params: ['value' => $value]
                )
            );
        }
    }

    /**
     * Compare with another DefaultWorkTime
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
