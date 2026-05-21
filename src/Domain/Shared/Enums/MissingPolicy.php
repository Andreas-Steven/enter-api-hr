<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum MissingPolicy: string
{
    case AUTO_CLOSE_BY_RULE = 'AUTO_CLOSE_BY_RULE';
    case IGNORE_MISSING_EVENT = 'IGNORE_MISSING_EVENT';
    case SEND_TO_REVIEW = 'SEND_TO_REVIEW';
    case TREAT_AS_ABSENT = 'TREAT_AS_ABSENT';

    public function label(): string
    {
        return match ($this) {
            self::AUTO_CLOSE_BY_RULE => 'Auto Close by Rule',
            self::IGNORE_MISSING_EVENT => 'Ignore Missing Event',
            self::SEND_TO_REVIEW => 'Send to Review',
            self::TREAT_AS_ABSENT => 'Treat as Absent',
        };
    }
}