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
        public ?array $location = null
    ) {
        if (!isset($info['in'], $info['out'])) {
            throw new InvalidArgumentException("Struktur 'info' tidak lengkap.");
        }
    }

    public static function fromArray(array $data): self
    {
        $startTime = isset($data['start_time']) && $data['start_time'] !== ''
            ? $data['start_time']
            : null;

        $info = $data['info'] ?? [];

        // 🔥 FIX: pastikan struktur selalu lengkap & tidak overwrite detail lama
        $info = [
            'in' => $info['in'] ?? true,
            'out' => $info['out'] ?? false,
            'detail' => $info['detail'] ?? [],
        ];

        return new self(
            employeeId: (int) $data['employee_id'],
            employeeName: $data['employee_name'] ?? null,
            info: $info,
            device: $data['device'],
            division: $data['division'] ?? null,
            startTime: $startTime,
            endTime: $data['end_time'] ?? null,
            workPattern: $data['work_pattern'] ?? $data['division']['work_pattern'] ?? null,
            location: $data['location'] ?? null
        );
    }

    /**
     * Logic sinkronisasi status berdasarkan business rules dari Yii logic.
     * Digunakan saat data baru masuk atau data lama diupdate.
     */
    public function withEvaluatedStatus(string $date): self
    {
        $newInfo = $this->info;
        // Ambil detail yang sudah ada, jika belum ada inisialisasi array kosong
        $currentDetails = $newInfo['detail'] ?? [];

        $dayOfWeek = (int) date('N', strtotime($date));
        $workingDays = $this->workPattern['working_days'] ?? [];

        $todaySchedule = null;
        foreach ($workingDays as $dayData) {
            if (isset($dayData['day_of_week']) && (int)$dayData['day_of_week'] === $dayOfWeek) {
                $todaySchedule = $dayData;
                break;
            }
        }

        if (!$todaySchedule) {
            if ($this->startTime && !in_array('Work Holiday', $currentDetails)) {
                $currentDetails[] = 'Work Holiday';
            }
            $newInfo['detail'] = $currentDetails;
            return $this->withInfo($newInfo);
        }

        $entryTime = $todaySchedule['shift']['start_time'] ?? null;
        $homeTime = $todaySchedule['shift']['end_time'] ?? null;

        // 🔥 Tambahan: ambil waktu sekarang (untuk MACHINE_STATUS & MISSED_CHECKIN)
        $currentTime = date('H:i:s');

        // =========================================
        // 1. MACHINE STATUS
        // =========================================
        if (empty($this->startTime) && !empty($this->endTime)) {
            if (
                strtotime($currentTime) >= strtotime('12:00:00') &&
                strtotime($currentTime) <= strtotime('15:00:00')
            ) {
                if (!in_array('Machine Status', $currentDetails)) {
                    $currentDetails[] = 'Machine Status';
                }
            }
        }

        // =========================================
        // 2. MISSED CHECKIN (langsung)
        // =========================================
        if (empty($this->startTime) && !empty($this->endTime)) {
            if (strtotime($currentTime) > strtotime('15:00:00')) {
                if (!in_array('Missed Checkin', $currentDetails)) {
                    $currentDetails[] = 'Missed Checkin';
                }
            }
        }

        // =========================================
        // 3. MISSED CHECKIN (dari MACHINE STATUS - UPDATE MODE)
        // =========================================
        if (in_array('Machine Status', $currentDetails)) {
            if (!in_array('Missed Checkin', $currentDetails)) {
                $currentDetails[] = 'Missed Checkin';
            }
        }

        // =========================================
        // 4. LATE (SUDAH ADA - tetap)
        // =========================================
        if ($entryTime && $this->startTime) {
            if (strtotime($this->startTime) > strtotime($entryTime)) {
                if (!in_array('Late', $currentDetails) && !in_array('Missed Checkin', $currentDetails)) {
                    $currentDetails[] = 'Late';
                }
            } else {
                $currentDetails = array_values(array_diff($currentDetails, ['Late']));
            }
        }

        // =========================================
        // 5. LEAVE EARLY (NEW + UPDATE MODE)
        // =========================================
        if ($homeTime && $this->endTime) {
            if (strtotime($this->endTime) < strtotime($homeTime)) {
                if (!in_array('Leave Early', $currentDetails) && !in_array('Machine Status', $currentDetails)) {
                    $currentDetails[] = 'Leave Early';
                }
            } else {
                // UPDATE MODE: remove jika sudah valid
                $currentDetails = array_values(array_diff($currentDetails, ['Leave Early']));
            }
        }

        // =========================================
        // 6. WORK HOLIDAY (sudah sebagian di atas, tapi ini versi lengkap Yii)
        // =========================================
        if (($entryTime === null && $homeTime === null)) {
            if (!empty($this->startTime) && !in_array('Work Holiday', $currentDetails)) {
                $currentDetails[] = 'Work Holiday';
            }
        }

        // =========================================
        // FINAL CLEANUP
        // =========================================
        $newInfo['detail'] = array_values(array_unique($currentDetails));
        
        // 🔥 FIX IN/OUT
        $newInfo['in'] = !empty($this->startTime);
        $newInfo['out'] = !empty($this->endTime);

        return $this->withInfo($newInfo);
    }

    /**
     * Digunakan khusus saat transisi status Active -> Complete (Closing Day).
     * Menambahkan label Missed Checkout jika karyawan masuk tapi tidak pernah absen pulang.
     * Atau label Override jika override.
     */
    public function withClosingEvaluated(): self
    {
        $newInfo = $this->info;
        $currentDetails = $newInfo['detail'] ?? [];

        // Logika: Jika ada jam masuk tapi TIDAK ADA jam pulang
        if (!empty($this->startTime) && empty($this->endTime)) {
            if (!in_array('Missed Checkout', $currentDetails)) {
                $currentDetails[] = 'Missed Checkout';
            }
        }

        // Sinkronisasi status boolean out jika missed checkout
        // (opsional, tergantung apakah 'out' tetap false atau dianggap true setelah missed)
        $newInfo['detail'] = array_values(array_unique($currentDetails));
        $newInfo['out'] = !empty($this->endTime);

        return $this->withInfo($newInfo);
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
            $this->location
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
            $this->location
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
            $this->location
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
            $this->location
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
            'detail' => [$isIn ? AttendanceStatus::MANUAL_IN : AttendanceStatus::MANUAL_OUT]
        ];
    }
}