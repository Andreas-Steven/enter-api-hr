<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum BranchType: int
{
    case OFFICE = 1;
    case RETAIL = 2;

    public function getLabel(): string
    {
        return match($this) {
            self::OFFICE => 'Office',
            self::RETAIL => 'Retail',
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