<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Service;

use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\Attendance\Entity\Attendance;

use App\Domain\AttendanceDevice\Repository\AttendanceDeviceRepositoryInterface;
use App\Domain\AttendanceDevice\Entity\AttendanceDevice;
use App\Domain\Attendance\Repository\AttendanceRepositoryInterface;
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;
use App\Domain\Employee\Entity\Employee;
use App\Domain\Shared\Enums\AttendanceStatus;
use App\Domain\Attendance\ValueObject\AttendanceItem;
use App\Domain\Attendance\ValueObject\AttendanceRecords;
use App\Domain\Attendance\ValueObject\AttendanceProcessResult;

use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * Attendance Domain Service
 * 
 * Pure business logic and domain rules
 */
final class AttendanceDomainService
{
    use DomainValidator;
    
    public function __construct(
        private DetailInfoFactory $detailInfoFactory,
        private AttendanceRepositoryInterface $repository,
        private EmployeeRepositoryInterface $employeeRepository,
        private AttendanceDeviceRepositoryInterface $attendanceDeviceRepository
    ) {}

    /**
     * Place Attendance-specific business logic here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     * 
     * Attendances:
     * - Complex validation rules
     * - Business calculations
     * - Domain-specific operations
     * - Cross-entity business rules
     */ 

    public function record(string $date, array $entries): Attendance
    {
        // 1. Bulk Load data employee
        $employeeIds = array_column($entries, 'employee_id');
        $employeeMasterData = $this->employeeRepository->findAllByIds($employeeIds);

        // 2. Transformasi ke snapshot (mengirimkan $date untuk evaluasi 'Late')
        $processedRecords = array_map(
            fn(array $entry) => $this->createSnapshot(
                $entry, 
                $employeeMasterData[$entry['employee_id']] ?? null,
                $date
            ),
            $entries
        );

        // 3. Sertakan parameter tambahan yang dibutuhkan oleh Entity
        return Attendance::create(
            date: $date, 
            attendance: $processedRecords,
            status: ResourceStatus::active(),
            detailInfo: []
        );
    }

    // With date and start time validation blacklist functionality
    public function prepareRecords(string $date, array $entries, array $existingEntries = []): AttendanceProcessResult
    {
        $attendanceResults = [];
        $blacklistResults = [];
        $today = date('Y-m-d');

        // 1. Ambil "Jam Terakhir yang Sah" dari data yang sudah ada di database (Existing)
        // Kita manfaatkan Value Object Records untuk mencari max start_time
        $recordsVO = AttendanceRecords::fromArray($existingEntries);
        $lastSystemTime = $recordsVO->getLatestStartTime();

        // 2. Hidrasi input baru ke VO & Sorting
        // Penting: Urutkan input mesin dari jam terkecil ke terbesar agar tidak saling blacklist
        usort($entries, fn($a, $b) => strtotime($a['start_time'] ?? '') <=> strtotime($b['start_time'] ?? ''));
        $itemObjects = array_map(fn(array $entry) => AttendanceItem::fromArray($entry), $entries);
        
        // 3. Bulk Load Master Data (Optimasi database)
        $employeeIds = AttendanceItem::collectEmployeeIds($itemObjects);
        $deviceCodes = AttendanceItem::collectDeviceCodes($itemObjects);
        $employeeMasterData = $this->employeeRepository->findAllByIds($employeeIds);
        $deviceMasterData = $this->attendanceDeviceRepository->findAllByCodes($deviceCodes);

        // 4. Map existing data untuk pengecekan update
        $existingMap = [];
        foreach ($existingEntries as $entry) {
            $existingMap[$entry['employee_id']] = $entry;
        }

        foreach ($itemObjects as $item) {
            $employee = $employeeMasterData[$item->employeeId] ?? null;
            $employeeName = $employee?->getName() ?? $item->employeeName;

            // --- VALIDASI 1: TANGGAL (Data Nyasar) ---
            if ($date !== $today) {
                $blacklistResults[] = [
                    'employee_id'   => $item->employeeId,
                    'employee_name' => $employeeName,
                    'reason'        => 'Invalid date submission (Expected: ' . $today . ')',
                    'submitted_at'  => $item->startTime,
                    'server_at'     => date('H:i:s')
                ];
                continue;
            }

            // --- VALIDASI 2: TIME TAMPERING (Jam Dimundurkan) ---
            // Jika ada start_time baru, bandingkan dengan jam terakhir yang sah di DB
            if ($item->startTime && $lastSystemTime && strtotime($item->startTime) < strtotime($lastSystemTime)) {
                $blacklistResults[] = [
                    'employee_id'   => $item->employeeId,
                    'employee_name' => $employeeName,
                    'reason'        => "Fraud Suspect: Backdated time. Last record: $lastSystemTime",
                    'submitted_at'  => $item->startTime,
                    'server_at'     => date('H:i:s')
                ];
                continue;
            }

            // --- PROSES ATTENDANCE NORMAL ---
            $deviceCode = $item->device['code'] ?? '';
            $device = $deviceMasterData[$deviceCode] ?? null;
            $existing = $existingMap[$item->employeeId] ?? null;

            if ($existing) {
                // Jika sudah ada, gabungkan jam (Entity-like logic di dalam VO)
                $item = AttendanceItem::fromArray($existing)
                    ->withStartTime($item->startTime)
                    ->withLatestEndTime($item->endTime);
            }

            // Simpan sebagai snapshot
            $attendanceResults[] = $this->createSnapshot(
                $item,
                $employee,
                $device,
                $today
            );

            // Update lastSystemTime secara dinamis (untuk pengecekan item berikutnya dalam batch yang sama)
            if ($item->startTime && (!$lastSystemTime || strtotime($item->startTime) > strtotime($lastSystemTime))) {
                $lastSystemTime = $item->startTime;
            }
        }

        return new AttendanceProcessResult($attendanceResults, $blacklistResults);
    }

    // public function prepareRecords(string $date, array $entries, array $existingEntries = []): AttendanceProcessResult
    // {
    //     $attendanceResults = [];
    //     $blacklistResults = [];
    //     $today = date('Y-m-d');

    //     // 1. Hidrasi data ke VO
    //     $itemObjects = array_map(fn(array $entry) => AttendanceItem::fromArray($entry), $entries);
        
    //     $employeeIds = AttendanceItem::collectEmployeeIds($itemObjects);
    //     $deviceCodes = AttendanceItem::collectDeviceCodes($itemObjects);

    //     $employeeMasterData = $this->employeeRepository->findAllByIds($employeeIds);
    //     $deviceMasterData = $this->attendanceDeviceRepository->findAllByCodes($deviceCodes);

    //     $existingMap = [];
    //     foreach ($existingEntries as $entry) {
    //         $existingMap[$entry['employee_id']] = $entry;
    //     }

    //     foreach ($itemObjects as $item) {
    //         // Cek apakah tanggal yang dikirimkan cocok dengan hari ini
    //         // Jika $date (dari command) != $today, maka ini data "nyasar"
    //         if ($date !== $today) {
    //             $blacklistResults[] = [
    //                 'employee_id'   => $item->employeeId,
    //                 'employee_name' => $employeeMasterData[$item->employeeId]?->getName() ?? $item->employeeName,
    //                 'submitted_date'=> $date,
    //                 'start_time'    => $item->startTime,
    //                 'reason'        => 'Invalid date submission (Expected: ' . $today . ')',
    //                 'actual_time'   => date('H:i:s')
    //             ];
    //             continue; // Jangan masukkan ke attendanceResults
    //         }

    //         // --- LOGIKA ATTENDANCE NORMAL ---
    //         $deviceCode = $item->device['code'] ?? '';
    //         $existing = $existingMap[$item->employeeId] ?? null;

    //         if ($existing) {
    //             $item = AttendanceItem::fromArray($existing)
    //                 ->withStartTime($item->startTime)
    //                 ->withLatestEndTime($item->endTime);
    //         }

    //         $attendanceResults[] = $this->createSnapshot(
    //             $item,
    //             $employeeMasterData[$item->employeeId] ?? null,
    //             $deviceMasterData[$deviceCode] ?? null,
    //             $today
    //         );
    //     }

    //     return new AttendanceProcessResult($attendanceResults, $blacklistResults);

    //     // return [
    //     //     'attendance' => $attendanceResults,
    //     //     'blacklist'  => $blacklistResults
    //     // ];
    // }

    private function createSnapshot(
        AttendanceItem $item, 
        ?Employee $employee,
        ?AttendanceDevice $device,
        string $date
    ): array {
        // 1. Ambil snapshot jadwal dan data master
        $workTimeSnapshot = $employee?->toDailyScheduleSnapshot($date);
        
        // 2. Buat objek item baru yang sudah diperkaya dengan data master
        // Ini penting agar withEvaluatedStatus() punya data workTime untuk dicek
        $enrichedItem = new AttendanceItem(
            employeeId: $item->employeeId,
            employeeName: $employee?->getName() ?? $item->employeeName,
            info: $item->info,
            device: $device?->toSnapshot() ?? AttendanceDevice::toUnregisteredSnapshot(),
            division: $employee?->toDivisionSnapshot() ?? Employee::toUnassignedDivisionSnapshot(),
            startTime: $item->startTime,
            endTime: $item->endTime,
            workTime: $workTimeSnapshot,
            location: $item->location
        );

        // 3. Jalankan evaluasi status (Late, Leave Early, dll)
        $finalItem = $enrichedItem->withEvaluatedStatus($date);

        // 4. Return array format database/snapshot
        return $finalItem->toArray();
    }

    private function evaluateStatus(AttendanceItem $item, ?Employee $employee, string $date): array
    {
        // Jika masih dibutuhkan secara mandiri, pastikan memanggil logic di VO
        return $item->withEvaluatedStatus($date)->info;
    }

    /**
     * Memproses penutupan data absensi (Active -> Complete).
     * Mengubah array mentah menjadi Object VO dan menjalankan evaluasi closing.
     * * @param array $entries Data attendance dari entity (array of array)
     * @return array Data yang sudah difinalisasi (array of array)
     */
    public function finalizeClosingRecords(array $entries): array
    {
        return array_map(function (array $entry) {
            // 1. Hidrasi: Ubah array mentah dari database menjadi Object VO
            $item = AttendanceItem::fromArray($entry);

            // 2. Eksekusi logika closing (Missed Checkout)
            $finalizedItem = $item->withClosingEvaluated();

            // 3. Kembalikan dalam bentuk array untuk disimpan kembali ke DB
            return $finalizedItem->toArray();
        }, $entries);
    }
}
