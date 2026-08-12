<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum ExcessTreatment: string
{
    case COUNT_AS_OVERTIME = 'COUNT_AS_OVERTIME';
    case IGNORE_EXCESS = 'IGNORE_EXCESS';
    case NO_OVERTIME = 'NO_OVERTIME';
    case REQUIRE_APPROVAL = 'REQUIRE_APPROVAL';

    public function label(): string
    {
        return match ($this) {
            self::COUNT_AS_OVERTIME => 'Count as Overtime',
            self::IGNORE_EXCESS => 'Ignore Excess',
            self::NO_OVERTIME => 'No Overtime',
            self::REQUIRE_APPROVAL => 'Require Approval',
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
