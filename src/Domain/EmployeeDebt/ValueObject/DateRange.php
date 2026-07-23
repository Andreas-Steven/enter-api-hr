<?php

declare(strict_types=1);

namespace App\Domain\EmployeeDebt\ValueObject;

final class DateRange
{
    private function __construct(
        public readonly string $startDate,
        public readonly string $endDate
    ) {}

    public static function fromFilterString(string $input): self
    {
        if (!str_contains($input, ',')) {
            return new self($input, $input);
        }
        
        [$start, $end] = array_map('trim', explode(',', $input));
        // Anda bisa menambahkan validasi DateTime di sini
        return new self($start, $end);
    }
}