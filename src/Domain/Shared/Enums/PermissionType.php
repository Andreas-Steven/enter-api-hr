<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum PermissionType: int
{
    case SICK = 1;
    case SICK_WITH_RECEIPT = 2;
    case LEAVE_EARLY = 3;
    case LATE = 4;
    case ANNUAL_LEAVE = 5;
    case PERMISSION_LEAVE = 6;
    case UNPAID_LEAVE = 7;

    public function getLabel(): string
    {
        return match($this) {
            self::SICK => 'Sick',
            self::SICK_WITH_RECEIPT => 'Sick with receipt',
            self::LEAVE_EARLY => 'Leave early',
            self::LATE => 'Late',
            self::ANNUAL_LEAVE => 'Annual leave',
            self::PERMISSION_LEAVE => 'Permission leave',
            self::UNPAID_LEAVE => 'Unpaid leave',
        };
    }

    /**
     * Replaces REVISION_PERMISSION_LIST logic
     */
    public function requiresRevision(): bool
    {
        return match($this) {
            self::LEAVE_EARLY, self::LATE => true,
            default => false,
        };
    }
}