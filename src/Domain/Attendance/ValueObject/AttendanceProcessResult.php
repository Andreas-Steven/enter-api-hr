<?php

declare(strict_types=1);

namespace App\Domain\Attendance\ValueObject;

use InvalidArgumentException;

// Value Object untuk menampung hasil
final class AttendanceProcessResult 
{
    public function __construct(
        public array $attendance, 
        public array $blacklist
    ) {}
}