<?php

declare(strict_types=1);

namespace App\Domain\AttendanceCalendar\ValueObject;

use App\Domain\Shared\Enums\AttendancePeriodConfig;
use App\Shared\ValueObject\Message;
use InvalidArgumentException;
use DateTimeImmutable;
use Yiisoft\FriendlyException\BadRequestException;

final readonly class AttendancePeriod
{
    private int $value;

    private function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function fromDateString(string $date): self
    {
        $dateTime = DateTimeImmutable::createFromFormat('Y-m-d', $date);

        if (!$dateTime) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'attendance.period.invalid_date_format',
                    params: [
                        'date' => $date,
                    ],
                    domain: 'app',
                )
            );
            throw new InvalidArgumentException("Format tanggal tidak valid: $date. Gunakan Y-m-d.");
        }

        if ((int) $dateTime->format('d') >= AttendancePeriodConfig::LAST_DATE) {
            $dateTime = $dateTime
                ->setDate(
                    (int) $dateTime->format('Y'),
                    (int) $dateTime->format('m'),
                    1
                )
                ->modify('+1 month');
        }

        $periodInt = (int) $dateTime->format('Ym');

        return new self($periodInt);
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
