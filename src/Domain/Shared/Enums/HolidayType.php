<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum HolidayType: string
{
    case COMPANY_HOLIDAY = 'COMPANY_HOLIDAY';
    case SPECIAL_DAY = 'SPECIAL_DAY';
    case WEEKLY_HOLIDAY = 'WEEKLY_HOLIDAY';
    case NATIONAL_HOLIDAY = 'NATIONAL_HOLIDAY';

    public function label(): string
    {
        return match ($this) {
            self::COMPANY_HOLIDAY => 'Company Holiday',
            self::SPECIAL_DAY => 'Special Day',
            self::WEEKLY_HOLIDAY => 'Weekly Holiday',
            self::NATIONAL_HOLIDAY => 'National Holiday',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}