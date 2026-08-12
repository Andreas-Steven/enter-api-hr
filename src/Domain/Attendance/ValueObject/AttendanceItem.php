<?php

declare(strict_types=1);

namespace App\Domain\Attendance\ValueObject;

use App\Domain\Shared\Enums\AttendanceStatus;
use InvalidArgumentException;

final readonly class AttendanceItem
{
    public function __construct(
        public int $employeeId,
        public ?string $employeeName,
        public array $info,
        public array $device,
        public ?array $division,
        public ?string $startTime,
        public ?string $endTime,
        // Changed from workTime to workPattern to reflect new structure
        public ?array $workPattern,
        public ?array $location = null,
        public ?array $branch = null,
        public ?array $attendancePolicies = null,
        public ?int $totalWorkMinutes = null
    ) {
        // No validation needed - normalization happens in fromArray()
    }

    public static function fromArray(array $data): self
    {
        $startTime = isset($data['start_time']) && $data['start_time'] !== ''
            ? $data['start_time']
            : null;

        $info = $data['info'] ?? [];
        
        // Normalize info structure before passing to constructor
        $info = self::normalizeInfoStatic($info);

        return new self(
            employeeId: (int) $data['employee_id'],
            employeeName: $data['employee_name'] ?? null,
            info: $info,
            device: $data['device'],
            division: $data['division'] ?? null,
            startTime: $startTime,
            endTime: $data['end_time'] ?? null,
            workPattern: $data['work_pattern'] ?? $data['branch']['work_pattern'] ?? null,
            location: $data['location'] ?? null,
            branch: $data['branch'] ?? null,
            attendancePolicies: $data['attendance_policies'] ?? null,
            totalWorkMinutes: $data['work_duration'] ?? $data['total_work_minutes'] ?? null
        );
    }

    private static function normalizeInfoStatic(array $info): array
    {
        // Handle old structure (detail as array of strings)
        if (isset($info['detail']) && is_array($info['detail']) && !empty($info['detail'])) {
            $firstDetail = $info['detail'][0] ?? null;
            if (is_string($firstDetail)) {
                // Convert old structure to new
                return [
                    'in' => $info['in'] ?? true,
                    'out' => $info['out'] ?? false,
                    'summary' => self::summaryFromOldDetailStatic($info['detail']),
                    'detail' => self::convertOldDetailToNewStatic($info['detail'])
                ];
            }
        }
        
        // Check if detail is already in new structure (object with boolean keys)
        if (isset($info['detail']) && is_array($info['detail']) && isset($info['detail']['late'])) {
            // Already in new structure, just ensure all fields exist
            return [
                'in' => $info['in'] ?? true,
                'out' => $info['out'] ?? false,
                'summary' => self::normalizeSummaryStatic($info['summary'] ?? null),
                'detail' => array_merge([
                    'late' => false,
                    'late_minutes' => 0,
                    'early_in' => 0,
                    'early_out' => 0,
                    'half_day' => false,
                    'full_day' => false,
                    'missing_in' => false,
                    'missing_out' => false
                ], $info['detail'])
            ];
        }
        
        // Empty or incomplete info - initialize with default structure
        return [
            'in' => $info['in'] ?? true,
            'out' => $info['out'] ?? false,
            'summary' => self::normalizeSummaryStatic($info['summary'] ?? null),
            'detail' => [
                'late' => false,
                'late_minutes' => 0,
                'early_in' => 0,
                'early_out' => 0,
                'half_day' => false,
                'full_day' => false,
                'missing_in' => false,
                'missing_out' => false
            ]
        ];
    }

    private static function normalizeSummaryStatic(mixed $summary): array
    {
        if (is_string($summary)) {
            $summary = trim($summary);

            return $summary === '' ? [] : [$summary];
        }

        if (!is_array($summary)) {
            return [];
        }

        $normalized = [];

        foreach ($summary as $item) {
            if (!is_string($item)) {
                continue;
            }

            $item = trim($item);
            if ($item === '' || in_array($item, $normalized, true)) {
                continue;
            }

            $normalized[] = $item;
        }

        return $normalized;
    }

    private static function summaryFromOldDetailStatic(array $oldDetails): array
    {
        $map = [
            'Full Day' => AttendanceStatus::FULL_DAY,
            'Half Day' => AttendanceStatus::HALF_DAY,
            'Missed Checkin' => AttendanceStatus::MISSED_CHECKIN,
            'Missed Checkout' => AttendanceStatus::MISSED_CHECKOUT,
            'Work Holiday' => AttendanceStatus::WORK_HOLIDAY,
            'Late' => AttendanceStatus::LATE,
            'Leave Early' => AttendanceStatus::LEAVE_EARLY,
            'Manual In' => AttendanceStatus::MANUAL_IN,
            'Manual Out' => AttendanceStatus::MANUAL_OUT,
        ];

        $summary = [];

        foreach ($map as $label => $status) {
            if (in_array($label, $oldDetails, true) && !in_array($status, $summary, true)) {
                $summary[] = $status;
            }
        }

        return $summary;
    }

    private static function convertOldDetailToNewStatic(array $oldDetails): array
    {
        return [
            'late' => in_array('Late', $oldDetails),
            'late_minutes' => 0, // Cannot determine from old structure
            'early_in' => 0, // Cannot determine from old structure
            'early_out' => in_array('Leave Early', $oldDetails) ? 0 : 0, // Cannot determine exact minutes
            'half_day' => in_array('Half Day', $oldDetails),
            'full_day' => in_array('Full Day', $oldDetails),
            'missing_in' => in_array('Missed Checkin', $oldDetails),
            'missing_out' => in_array('Missed Checkout', $oldDetails)
        ];
    }

    /**
     * Logic sinkronisasi status berdasarkan business rules dari Yii logic.
     * Digunakan saat data baru masuk atau data lama diupdate.
     */
    public function withEvaluatedStatus(string $date): self
    {
        $newInfo = $this->info;

        // Initialize new detail structure
        $detail = [
            'late' => false,
            'late_minutes' => 0,
            'early_in' => 0,
            'early_out' => 0,
            'half_day' => false,
            'full_day' => false,
            'missing_in' => false,
            'missing_out' => false
        ];

        $dayOfWeek = (int) date('N', strtotime($date));
        $workingDays = $this->workPattern['working_days'] ?? [];

        $todaySchedule = null;
        foreach ($workingDays as $dayData) {
            if (isset($dayData['day_of_week']) && (int)$dayData['day_of_week'] === $dayOfWeek) {
                $todaySchedule = $dayData;
                break;
            }
        }

        // Extract the work pattern rule for this day (if any)
        $rule = $todaySchedule['work_pattern_rule'] ?? null;

        if (!$todaySchedule || ($todaySchedule['shift'] ?? null) === null) {
            // No shift scheduled for this day — not an obligation day
            $newInfo['summary'] = [AttendanceStatus::WORK_HOLIDAY];
            $newInfo['detail'] = $detail;
            $newInfo['in'] = !empty($this->startTime);
            $newInfo['out'] = !empty($this->endTime);
            $newInfo['is_obligation_day'] = false;
            $newInfo['work_pattern_rule'] = $rule;
            return $this->withInfo($newInfo);
        }

        $entryTime = $todaySchedule['shift']['start_time'] ?? null;
        $homeTime = $todaySchedule['shift']['end_time'] ?? null;

        // 🔥 Tambahan: ambil waktu sekarang (untuk MACHINE_STATUS & MISSED_CHECKIN)
        $currentTime = date('H:i:s');

        // =========================================
        // 1. MACHINE STATUS
        // =========================================
        $machineStatus = false;
        if (empty($this->startTime) && !empty($this->endTime)) {
            if (
                strtotime($currentTime) >= strtotime('12:00:00') &&
                strtotime($currentTime) <= strtotime('15:00:00')
            ) {
                $machineStatus = true;
            }
        }

        // =========================================
        // 2. MISSED CHECKIN
        // =========================================
        if (empty($this->startTime) && !empty($this->endTime)) {
            if (strtotime($currentTime) > strtotime('15:00:00') || $machineStatus) {
                $detail['missing_in'] = true;
            }
        }

        // =========================================
        // 3. LATE (dengan grace period dari attendance_policies)
        // =========================================
        if ($entryTime && $this->startTime) {
            $lateGraceIn = 0;
            if ($this->attendancePolicies) {
                $policyDetail = $this->attendancePolicies['policy_detail'] ?? null;
                $lateGraceIn = $policyDetail['late_grace_in'] ?? 0;
            }

            // Hitung batas waktu masuk dengan grace period
            $lateThreshold = strtotime($entryTime) + ($lateGraceIn * 60);

            if (strtotime($this->startTime) > $lateThreshold) {
                $detail['late'] = true;
                $detail['late_minutes'] = (int) round((strtotime($this->startTime) - strtotime($entryTime)) / 60);
            }
        }

        // =========================================
        // 4. EARLY IN (calculate if came before shift start)
        // =========================================
        if ($entryTime && $this->startTime) {
            if (strtotime($this->startTime) < strtotime($entryTime)) {
                $detail['early_in'] = (int) round((strtotime($entryTime) - strtotime($this->startTime)) / 60);
            }
        }

        // =========================================
        // 5. LEAVE EARLY (dengan grace period dari attendance_policies)
        // =========================================
        if ($homeTime && $this->endTime) {
            $earlyGraceOut = 0;
            if ($this->attendancePolicies) {
                $policyDetail = $this->attendancePolicies['policy_detail'] ?? null;
                $earlyGraceOut = $policyDetail['early_grace_out'] ?? 0;
            }

            // Hitung batas waktu pulang dengan grace period
            $earlyThreshold = strtotime($homeTime) - ($earlyGraceOut * 60);

            if (strtotime($this->endTime) < $earlyThreshold) {
                $detail['early_out'] = (int) round((strtotime($homeTime) - strtotime($this->endTime)) / 60);
            }
        }

        // =========================================
        // 6. CALCULATE TOTAL WORK MINUTES
        // =========================================
        $totalWorkMinutes = null;
        if ($this->startTime && $this->endTime) {
            $startTimestamp = strtotime($this->startTime);
            $endTimestamp = strtotime($this->endTime);
            $totalWorkMinutes = (int) round(($endTimestamp - $startTimestamp) / 60);
        }

        // =========================================
        // 7. DETERMINE FULL_DAY / HALF_DAY based on attendance_policies
        // =========================================
        if ($totalWorkMinutes !== null && $this->attendancePolicies) {
            $policyDetail = $this->attendancePolicies['policy_detail'] ?? null;
            $minWorkFullDay = $policyDetail['min_work_full_day'] ?? null;
            $minWorkHalfDay = $policyDetail['min_work_half_day'] ?? null;

            if ($minWorkFullDay !== null && $totalWorkMinutes >= $minWorkFullDay) {
                $detail['full_day'] = true;
            } elseif ($minWorkHalfDay !== null && $totalWorkMinutes >= $minWorkHalfDay) {
                $detail['half_day'] = true;
            }
        }

        // =========================================
        // DETERMINE SUMMARY
        // =========================================
        $summary = [];
        if ($detail['missing_in']) {
            $summary[] = AttendanceStatus::MISSED_CHECKIN;
        } elseif ($detail['missing_out']) {
            $summary[] = AttendanceStatus::MISSED_CHECKOUT;
        } elseif ($detail['full_day']) {
            $summary[] = AttendanceStatus::FULL_DAY;
        } elseif ($detail['half_day']) {
            $summary[] = AttendanceStatus::HALF_DAY;
        } elseif ($detail['late']) {
            $summary[] = AttendanceStatus::LATE;
        } elseif ($detail['early_out'] > 0) {
            $summary[] = AttendanceStatus::LEAVE_EARLY;
        }

        // =========================================
        // FINAL UPDATE
        // =========================================
        $newInfo['summary'] = $summary;
        $newInfo['detail'] = $detail;
        $newInfo['in'] = !empty($this->startTime);
        $newInfo['out'] = !empty($this->endTime);
        $newInfo['is_obligation_day'] = true; // Has shift → obligation day by default
        $newInfo['work_pattern_rule'] = $rule;

        return $this->withInfo($newInfo)->withTotalWorkMinutes($totalWorkMinutes);
    }

    /**
     * Digunakan khusus saat transisi status Active -> Complete (Closing Day).
     * Menambahkan label Missed Checkout jika karyawan masuk tapi tidak pernah absen pulang.
     * Atau label Override jika override.
     */
    public function withClosingEvaluated(): self
    {
        $newInfo = $this->info;
        $detail = $newInfo['detail'] ?? [
            'late' => false,
            'late_minutes' => 0,
            'early_in' => 0,
            'early_out' => 0,
            'half_day' => false,
            'full_day' => false,
            'missing_in' => false,
            'missing_out' => false
        ];

        // Logika: Jika ada jam masuk tapi TIDAK ADA jam pulang
        if (!empty($this->startTime) && empty($this->endTime)) {
            $detail['missing_out'] = true;
            $newInfo['summary'] = [AttendanceStatus::MISSED_CHECKOUT];
        }

        // Sinkronisasi status boolean out jika missed checkout
        $newInfo['detail'] = $detail;
        $newInfo['out'] = !empty($this->endTime);

        return $this->withInfo($newInfo)->withTotalWorkMinutes($this->totalWorkMinutes);
    }

    /**
     * Helper untuk membuat instance baru dengan info yang diperbarui
     */
    public function withInfo(array $info): self
    {
        return new self(
            $this->employeeId,
            $this->employeeName,
            $info,
            $this->device,
            $this->division,
            $this->startTime,
            $this->endTime,
            $this->workPattern,
            $this->location,
            $this->branch,
            $this->attendancePolicies,
            $this->totalWorkMinutes
        );
    }

    public function isLate(): bool
    {
        return in_array('Late', $this->info['detail'] ?? []);
    }

    public function toArray(): array
    {
        return [
            'employee_id' => $this->employeeId,
            'employee_name' => $this->employeeName,
            'info' => $this->info,
            'device' => $this->device,
            'division' => $this->division,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'work_pattern' => $this->workPattern,
            'location' => $this->location,
            'branch' => $this->branch,
            'attendance_policies' => $this->attendancePolicies,
            'work_duration' => $this->totalWorkMinutes,
        ];
    }

    /**
     * @param self[] $items
     * @return int[]
     */
    public static function collectEmployeeIds(array $items): array
    {
        return array_values(array_unique(
            array_map(fn(self $item) => $item->employeeId, $items)
        ));
    }

    /**
     * @param self[] $items
     * @return string[]
     */
    public static function collectDeviceCodes(array $items): array
    {
        return array_values(array_unique(array_filter(
            array_map(fn(self $item) => $item->device['code'] ?? null, $items)
        )));
    }

    public function withStartTime(?string $time): self
    {
        // Jika data lama kosong, tidak perlu melakukan apa-apa
        if (empty($time)) {
            return $this;
        }

        // Aturan Bisnis: Jika sudah ada nilainya, jangan ubah
        if ($this->startTime !== null) {
            return $this;
        }

        // Hanya jika startTime aslinya null, baru kita buat objek baru
        return new self(
            $this->employeeId,
            $this->employeeName,
            $this->info,
            $this->device,
            $this->division,
            $time, // Menimpa hanya jika kosong
            $this->endTime,
            $this->workPattern,
            $this->location,
            $this->branch,
            $this->attendancePolicies,
            $this->totalWorkMinutes
        );
    }

    public function forceStartTime(string $time): self
    {
        // Kita buat instance baru secara paksa dengan nilai lama
        return new self(
            $this->employeeId,
            $this->employeeName,
            $this->info,
            $this->device,
            $this->division,
            $time, // Memaksa isi dengan 08:00:00
            $this->endTime,
            $this->workPattern,
            $this->location,
            $this->branch,
            $this->attendancePolicies,
            $this->totalWorkMinutes
        );
    }

    public function withLatestEndTime(?string $time): self
    {
        // Jika waktu baru kosong, abaikan saja
        if (empty($time)) {
            return $this;
        }

        // Tidak perlu cek apakah sudah ada nilainya,
        // karena endTime memang harus selalu update ke yang paling baru (terakhir)
        return new self(
            $this->employeeId,
            $this->employeeName,
            $this->info,
            $this->device,
            $this->division,
            $this->startTime,
            $time, // Menimpa dengan waktu terbaru
            $this->workPattern,
            $this->location,
            $this->branch,
            $this->attendancePolicies,
            $this->totalWorkMinutes
        );
    }

    public function withTotalWorkMinutes(?int $minutes): self
    {
        return new self(
            $this->employeeId,
            $this->employeeName,
            $this->info,
            $this->device,
            $this->division,
            $this->startTime,
            $this->endTime,
            $this->workPattern,
            $this->location,
            $this->branch,
            $this->attendancePolicies,
            $minutes
        );
    }

    /**
     * Membuat info array untuk manual adjustment
     */
    public static function createManualInfo(bool $isIn): array
    {
        return [
            'in' => $isIn,
            'out' => !$isIn,
            'summary' => [$isIn ? AttendanceStatus::MANUAL_IN : AttendanceStatus::MANUAL_OUT],
            'detail' => [
                'late' => false,
                'late_minutes' => 0,
                'early_in' => 0,
                'early_out' => 0,
                'half_day' => false,
                'full_day' => false,
                'missing_in' => false,
                'missing_out' => false
            ]
        ];
    }
}