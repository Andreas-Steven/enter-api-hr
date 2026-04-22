<?php

declare(strict_types=1);

namespace App\Domain\Attendance\ValueObject;

use App\Domain\Shared\Enums\AttendanceStatus;
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

final class AttendanceRecord
{
    private function __construct(
        private readonly int $employeeId,
        private readonly string $employeeName,
        private readonly ?string $startTime,
        private readonly ?string $endTime,
        private readonly array $device,
        private readonly array $location,
        private readonly array $division,
        private readonly array $workTime,
        private readonly array $info
    ) {}

    private function validate(): void
    {
        if ($this->employeeId < 0) {
            throw new BadRequestException(
                Message::create('number.invalid', ['field' => 'employee_id'], 'validation')
            );
        }

        if (empty($this->startTime) && empty($this->endTime)) {
            throw new BadRequestException(
                Message::create('attendance.time_empty', domain: 'app')
            );
        }
    }

    public function evaluateBusinessRules(
        string $date,
        bool $isDayOff,
        ?string $lastStartTime,
        ?string $dateMismatch,
    ): self {
        $currentTime = date('H:i:s');
        $workingStart = $this->startTime;
        $workingEnd   = $this->endTime;
        $workingInfo  = $this->info;
        $workingInfo['detail'] ??= [];
        $workingInfo['in']  ??= false;
        $workingInfo['out'] ??= false;

        // 1. DEVICE APP LOCATION VALIDATION (MATCH Yii2)
        if (($this->device['code'] ?? '') === AttendanceStatus::DEVICE_APP) {
            $this->validateAppLocation();
        }

        // 2. START TIME VALIDATION BEFORE NOON (MATCH Yii2)
        if (strtotime($currentTime) < strtotime(AttendanceStatus::TIME_NOON)) {
            if (empty($workingStart) && !empty($workingEnd)) {
                $workingStart = $workingEnd;
            }

            if (empty($workingStart) || strtotime($workingStart) === false) {
                throw new BadRequestException(
                    Message::create('attendance.start_invalid', domain: 'app')
                );
            }
        }

        // 3. MACHINE STATUS (12:00 - 15:00)
        if (empty($workingStart) && !empty($workingEnd)) {
            $currentTs = strtotime($currentTime);
            if ($currentTs >= strtotime(AttendanceStatus::TIME_NOON) && $currentTs <= strtotime(AttendanceStatus::TIME_AFTERNOON)) {
                $workingInfo = $this->addStatus($workingInfo, AttendanceStatus::MACHINE_STATUS);
            }
        }

        // 4. MISSED CHECKIN AFTER 15:00
        if (empty($workingStart) && !empty($workingEnd)) {
            if (strtotime($currentTime) > strtotime(AttendanceStatus::TIME_AFTERNOON)) {
                $workingStart = $workingEnd;
                $workingInfo = $this->addStatus($workingInfo, AttendanceStatus::MISSED_CHECKIN);
            }
        }
        
        // 5. BLACKLIST LOGIC (MATCH Yii2 EXACT)
        if (!empty($lastStartTime) || $dateMismatch) {
            if ($workingStart && $lastStartTime && strtotime($workingStart) < strtotime($lastStartTime)) {
                $workingStart = $currentTime;
                $workingInfo = $this->addStatus($workingInfo, AttendanceStatus::BLACKLIST);
            }

            if ($dateMismatch) {
                $workingStart = $currentTime;
                $workingInfo = $this->addStatus($workingInfo, AttendanceStatus::BLACKLIST);
            }
        }

        // 6. END TIME VALIDATION (MATCH Yii2 UPDATE LOGIC)
        if ($workingStart && $workingEnd && !in_array(AttendanceStatus::MISSED_CHECKIN, $workingInfo['detail'], true)) {
            if (strtotime($workingEnd) < strtotime($workingStart)) {
                throw new BadRequestException(
                    Message::create('attendance.end_before_start', domain: 'app')
                );
            }
        }

        // 7. WORKING DAY / SCHEDULE LOGIC
        $dayNow = (int) date('N', strtotime($date));
        $workingDay = $this->workTime['working_days'][$dayNow] ?? null;

        if (!$workingDay || $isDayOff) {
            if ($workingStart) {
                $workingInfo = $this->addStatus($workingInfo, AttendanceStatus::WORK_HOLIDAY);
            }
        } else {
            // LATE
            if ($workingStart && ($workingDay['entry_time'] ?? null)) {
                if (strtotime($workingStart) > strtotime($workingDay['entry_time'])) {
                    if (!in_array(AttendanceStatus::MISSED_CHECKIN, $workingInfo['detail'], true)) {
                        $workingInfo = $this->addStatus($workingInfo, AttendanceStatus::LATE);
                    }
                }
            }

            // LEAVE EARLY
            if ($workingEnd && ($workingDay['home_time'] ?? null)) {
                if (strtotime($workingEnd) < strtotime($workingDay['home_time'])) {
                    if (!in_array(AttendanceStatus::MACHINE_STATUS, $workingInfo['detail'], true)) {
                        $workingInfo = $this->addStatus($workingInfo, AttendanceStatus::LEAVE_EARLY);
                    }
                } else {
                    // REMOVE LEAVE EARLY IF NOT VALID ANYMORE (MATCH Yii2 UPDATE)
                    $workingInfo['detail'] = array_values(
                        array_filter(
                            $workingInfo['detail'],
                            fn($s) => $s !== AttendanceStatus::LEAVE_EARLY
                        )
                    );
                }
            }
        }

        // 8. FINAL FLAG ASSIGNMENT (MATCH Yii2)
        $workingInfo['in']  = !empty($workingStart);
        $workingInfo['out'] = !empty($workingEnd);

        return new self(
            employeeId: $this->employeeId,
            employeeName: $this->employeeName,
            startTime: $workingStart,
            endTime: $workingEnd,
            device: $this->device,
            location: $this->location,
            division: $this->division,
            workTime: $this->workTime,
            info: $workingInfo
        );
    }

    public function validateAppLocation(): void
    {
        $lat = $this->location['lat'] ?? null;
        $lng = $this->location['lng'] ?? null;
        
        if ($lat === null || $lng === null) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'location.coordinates.required',
                    domain: 'app',
                )
            );
        }

        // Validate latitude range (-90 to 90)
        if ($lat < -90 || $lat > 90) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'invalid.latitude',
                    domain: 'app',
                )
            );
        }

        // Validate longitude range (-180 to 180)
        if ($lng < -180 || $lng > 180) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'invalid.longitude',
                    domain: 'app',
                )
            );
        }
    }

    // STATUS HELPER
    private function addStatus(array $info, string $status): array
    {
        if (!in_array($status, $info['detail'], true)) {
            $info['detail'][] = $status;
        }
        return $info;
    }

    public function getStartTime(): ?string
    {
        return $this->startTime;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            employeeId: (int) ($data['employee_id'] ?? 0),
            employeeName: $data['employee_name'] ?? '',
            startTime: $data['start_time'] ?? null,
            endTime: $data['end_time'] ?? null,
            device: $data['device'] ?? [],
            location: $data['location'] ?? [],
            division: $data['division'] ?? [],
            workTime: $data['work_time'] ?? [],
            info: $data['info'] ?? [
                'detail' => [],
                'in' => false,
                'out' => false
            ]
        );
    }

    public static function fromJson(?string $jsonString): ?self
    {
        if (empty($jsonString)) {
            return null;
        }

        $data = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
            return null;
        }

        return self::fromArray($data);
    }

    public static function jsonToArray(?string $jsonString): array
    {
        if (empty($jsonString)) {
            return [];
        }

        $data = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
            return [];
        }

        // Handle both single record and array of records
        if (isset($data['employee_id'])) {
            // Single record
            return [$data];
        }

        // Array of records
        return is_array($data) ? $data : [];
    }

    public function toArray(): array
    {
        return [
            'employee_id' => $this->employeeId,
            'employee_name' => $this->employeeName,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'device' => $this->device,
            'location' => $this->location,
            'division' => $this->division,
            'work_time' => $this->workTime,
            'info' => $this->info,
        ];
    }
}