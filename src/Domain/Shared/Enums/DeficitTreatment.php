<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum DeficitTreatment: string
{
    case MANUAL_REVIEW = 'MANUAL_REVIEW';
    case TREAT_AS_ABSENT = 'TREAT_AS_ABSENT';
    case USE_EMPLOYEE_LEAVE = 'USE_EMPLOYEE_LEAVE';

    public function label(): string
    {
        return match ($this) {
            self::MANUAL_REVIEW => 'Manual Review',
            self::TREAT_AS_ABSENT => 'Treat as Absent',
            self::USE_EMPLOYEE_LEAVE => 'Use Employee Leave',
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
