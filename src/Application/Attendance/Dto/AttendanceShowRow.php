<?php

declare(strict_types=1);

namespace App\Application\Attendance\Dto;

final readonly class AttendanceShowRow
{
    public function __construct(
        private array $attributes,
        private array $attendance,
    ) {
    }

    private function getAttributesWithoutBlacklist(): array
    {
        $attributes = $this->attributes;

        if (is_array($attributes['detail_info'] ?? null)) {
            unset($attributes['detail_info']['blacklist']);
        }

        return $attributes;
    }

    public static function fromArray(array $row): self
    {
        $attendance = array_map(
            static fn(array $record): AttendanceShowRecord => AttendanceShowRecord::fromArray($record),
            is_array($row['attendance'] ?? null) ? $row['attendance'] : []
        );

        unset($row['attendance']);

        return new self(
            attributes: $row,
            attendance: $attendance,
        );
    }

    public function filterAttendanceByEmployee(int $employeeId): self
    {
        return new self(
            attributes: $this->attributes,
            attendance: array_values(array_filter(
                $this->attendance,
                static fn(AttendanceShowRecord $record): bool => $record->belongsToEmployee($employeeId)
            )),
        );
    }

    public function hasAttendance(): bool
    {
        return $this->attendance !== [];
    }

    public function toArray(): array
    {
        return [
            ...$this->getAttributesWithoutBlacklist(),
            'attendance' => array_map(
                static fn(AttendanceShowRecord $record): array => $record->toArray(),
                $this->attendance,
            ),
        ];
    }

    /**
     * Mengembalikan instance baru dengan daftar attendance yang sudah difilter
     */
    public function filterByEmployeeName(string $name): self
    {
        $filteredAttendance = array_values(array_filter($this->attendance, function (AttendanceShowRecord $record) use ($name) {
            // Karena $record adalah Object, kita konversi ke array dulu 
            // atau gunakan getter jika ada (misal: $record->getEmployeeName())
            $recordData = $record->toArray();
            
            return stripos($recordData['employee_name'] ?? '', $name) !== false;
        }));

        // Gunakan constructor yang benar (hanya attributes dan attendance)
        return new self(
            attributes: $this->attributes,
            attendance: $filteredAttendance
        );
    }

    /**
     * Memfilter daftar attendance berdasarkan nama dan/atau ID divisi
     */
    public function applyFilters(?string $name, ?int $divisionId): self
    {
        // Jika tidak ada filter yang aktif, return self (Optimization)
        if ($name === null && $divisionId === null) {
            return $this;
        }

        $filteredAttendance = array_values(array_filter(
            $this->attendance,
            static fn(AttendanceShowRecord $record): bool => $record->matchesFilters($name, $divisionId)
        ));

        return new self(
            attributes: $this->attributes,
            attendance: $filteredAttendance
        );
    }
}
