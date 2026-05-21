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
}