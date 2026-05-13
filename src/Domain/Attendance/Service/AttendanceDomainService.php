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

    // public function prepareRecords(string $date, array $entries): array
    public function prepareRecords(string $date, array $entries, array $existingEntries = []): array
    {
        // 1. Bulk Load Employee
        $itemObjects = array_map(fn(array $entry) => AttendanceItem::fromArray($entry), $entries);
        
        $employeeIds = AttendanceItem::collectEmployeeIds($itemObjects);
        $deviceCodes = AttendanceItem::collectDeviceCodes($itemObjects);

        $employeeMasterData = $this->employeeRepository->findAllByIds($employeeIds);
        $deviceMasterData = $this->attendanceDeviceRepository->findAllByCodes($deviceCodes);

        // 2. Create map for existing entries
        $existingMap = [];
        foreach ($existingEntries as $entry) {
            $existingMap[$entry['employee_id']] = $entry;
        }

        return array_map(
            function (AttendanceItem $item) use ($employeeMasterData, $deviceMasterData, $date, $existingMap) {

                $deviceCode = $item->device['code'] ?? '';

                $existing = $existingMap[$item->employeeId] ?? null;

                // 🔥 IMPORTANT: merge info lama
                if ($existing) {
                    $item = AttendanceItem::fromArray($existing)
                        ->withStartTime($item->startTime)
                        ->withLatestEndTime($item->endTime);
                }

                return $this->createSnapshot(
                    $item,
                    $employeeMasterData[$item->employeeId] ?? null,
                    $deviceMasterData[$deviceCode] ?? null,
                    $date
                );
            },
            $itemObjects
        );

        // return array_map(
        //     function (AttendanceItem $item) use ($employeeMasterData, $deviceMasterData, $date) {
        //         // Akses data langsung dari properti objek, BUKAN dari array
        //         $deviceCode = $item->device['code'] ?? '';
                
        //         return $this->createSnapshot(
        //             $item, // Kirim objeknya langsung
        //             $employeeMasterData[$item->employeeId] ?? null,
        //             $deviceMasterData[$deviceCode] ?? null,
        //             $date
        //         );
        //     },
        //     $itemObjects
        // );
    }

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
     * Logic dipindahkan dari Application Service (Standard DDD)
     * Menangani manipulasi manual dari Admin untuk record yang sudah ada.
     */
    public function applyManualAdjustment(AttendanceItem $existing, array $itemData): AttendanceItem
    {
        $status = $itemData['manual_status'];
        $newTime = $itemData['start_time'] ?? $itemData['end_time'];
        
        $newInfo = $existing->info;
        $overrideLabel = $status === AttendanceStatus::MANUAL_IN ? AttendanceStatus::MANUAL_IN : AttendanceStatus::MANUAL_OUT;
        if (!in_array($overrideLabel, $newInfo['detail'])) {
            $newInfo['detail'][] = $overrideLabel;
        }

        if ($status === AttendanceStatus::MANUAL_IN) {
            return $existing->forceStartTime($newTime)->withInfo($newInfo);
        }

        if ($status === AttendanceStatus::MANUAL_OUT) {
            return $existing->withLatestEndTime($newTime)->withInfo($newInfo);
        }

        return $existing;
    }

    /**
     * Membuat AttendanceItem baru untuk manual adjustment dengan enrichment data master.
     * Pattern sama seperti createSnapshot, menangani enrichment di Domain Service.
     */
    public function createManualAdjustmentItem(
        int $employeeId,
        ?Employee $employee,
        string $date,
        array $itemData
    ): AttendanceItem {
        $status = $itemData['manual_status'];
        $time = $itemData['start_time'] ?? $itemData['end_time'];
        $isIn = $status === AttendanceStatus::MANUAL_IN;

        $workTimeSnapshot = $employee?->toDailyScheduleSnapshot($date);

        return new AttendanceItem(
            employeeId: $employeeId,
            employeeName: $employee?->getName() ?? '',
            info: AttendanceItem::createManualInfo($isIn),
            device: AttendanceDevice::toManualSnapshot(),
            division: $employee?->toDivisionSnapshot() ?? Employee::toUnassignedDivisionSnapshot(),
            startTime: $isIn ? $time : null,
            endTime: !$isIn ? $time : null,
            workTime: $workTimeSnapshot,
            location: null
        );
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
