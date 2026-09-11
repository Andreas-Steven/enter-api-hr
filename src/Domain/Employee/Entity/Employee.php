<?php

declare(strict_types=1);

namespace App\Domain\Employee\Entity;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;

// Domain Layer
use App\Domain\Shared\Concerns\Entity\Stateful;
use App\Domain\Shared\Concerns\Entity\Identifiable;
use App\Domain\Shared\Concerns\Entity\Descriptive;

use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;
use App\Domain\Employee\ValueObject\DateResign;
use App\Domain\Employee\ValueObject\DateStart;
use App\Domain\Employee\ValueObject\BirthDate;
use App\Domain\Employee\ValueObject\Contact;
use App\Domain\Employee\ValueObject\Image;

final class Employee
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'Employee';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private string $username,
        private int $ssoId,
        private ?string $nik,
        private ?string $email,
        private ?int $divisionId,
        private ?int $positionId,
        private ?int $employeeTypeId,
        private ?int $branchId,
        private ?int $gradeId,
        private ?string $birthDate,
        private ?string $dateStart,
        private ?string $dateResign,
        private ?Contact $contact,
        private ?Image $image,
        private ResourceStatus $status,
        private DetailInfo $detailInfo,
        private ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ) {
        $this->resource = self::RESOURCE;
        $this->lockVersion = $lockVersion ?? LockVersion::create();
    }

    public static function getResource(): string
    {
        return self::RESOURCE;
    }

    public static function create(
        string $name,
        string $username,
        int $ssoId,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?Image $image,
        ?Contact $contact = null,
        ?string $birthDate = null,
        ?string $dateStart = null,
        ?string $dateResign = null,
        ?string $nik = null,
        ?string $email = null,
        ?int $divisionId = null,
        ?int $positionId = null,
        ?int $employeeTypeId = null,
        ?int $branchId = null,
        ?int $gradeId = null,
        ?SyncMdb $syncMdb = null,
    ): self {
        self::guardInitialStatus(
            status: $status,
            resource: self::RESOURCE
        );

        return new self(
            id: null,
            name: $name,
            username: $username,
            ssoId: $ssoId,
            nik: $nik,
            email: $email,
            contact: $contact,
            birthDate: $birthDate,
            dateStart: $dateStart,
            dateResign: $dateResign,
            divisionId: $divisionId,
            positionId: $positionId,
            employeeTypeId: $employeeTypeId,
            branchId: $branchId,
            gradeId: $gradeId,
            image: $image,
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            lockVersion: LockVersion::create()
        );
    }

    public static function reconstitute(
        int $id,
        string $name,
        string $username,
        int $ssoId,
        ?string $nik,
        ?string $email,
        ?int $divisionId,
        ?int $positionId,
        ?int $employeeTypeId,
        ?int $branchId,
        ?int $gradeId,
        ?string $birthDate,
        ?string $dateStart,
        ?string $dateResign,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?Contact $contact,
        ?Image $image,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ): self {

        return new self(
            id: $id,
            name: $name,
            username: $username,
            ssoId: $ssoId,
            nik: $nik,
            email: $email,
            contact: $contact,
            divisionId: $divisionId,
            positionId: $positionId,
            employeeTypeId: $employeeTypeId,
            branchId: $branchId,
            gradeId: $gradeId,
            image: $image,
            birthDate: $birthDate,
            dateStart: $dateStart,
            dateResign: $dateResign,
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            lockVersion: $lockVersion ?? LockVersion::create()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'sso_id' => $this->ssoId,
            'nik' => $this->nik,
            'email' => $this->email,
            'contact' => $this->contact?->toArray(),
            'birth_date' => $this->birthDate,
            'date_start' => $this->dateStart,
            'date_resign' => $this->dateResign,
            'division_id' => $this->divisionId,
            'position_id' => $this->positionId,
            'employee_type_id' => $this->employeeTypeId,
            'branch_id' => $this->branchId,
            'grade_id' => $this->gradeId,
            'image' => $this->image?->toArray(),
            'status' => $this->status->value(),
            'detail_info' => $this->detailInfo->toArray(),
            SyncMdb::field() => $this->syncMdb?->value(),
            LockVersion::field() => $this->lockVersion->value(),
        ];
    }

    public function toPersistence(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
    
    public function restore(): void
    {
        if (!$this->status->isDeleted()) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'resource.not_deleted',
                    params: ['id' => $this->id]
                )
            );
        }
        
        $this->status = ResourceStatus::restored();
    }

    /**
     * Place Employee-specific business functions here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     */ 
    public function getName(): string
    {
        return $this->name;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getSsoId(): int
    {
        return $this->ssoId;
    }

    public function getNik(): ?string
    {
        return $this->nik;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getBirthDate(): ?string
    {
        return $this->birthDate;
    }

    public function getDateStart(): ?string
    {
        return $this->dateStart;
    }

    public function getDateResign(): ?string
    {
        return $this->dateResign;
    }

    public function getDivisionId(): ?int
    {
        return $this->divisionId;
    }

    public function getPositionId(): ?int
    {
        return $this->positionId;
    }

    public function getEmployeeTypeId(): ?int
    {
        return $this->employeeTypeId;
    }

    public function getBranchId(): ?int
    {
        return $this->branchId;
    }

    public function getGradeId(): ?int
    {
        return $this->gradeId;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function getImage(): ?Image
    {
        return $this->image;
    }

    public function toDivisionSnapshot(): array
    {
        $division = $this->detailInfo->get('division');
        
        return [
            'id' => $division['id'] ?? $this->divisionId,
            'code' => $division['code'] ?? null,
            'name' => $division['name'] ?? 'N/A',
        ];
    }

    public function toBranchSnapshot(): array
    {
        $branch = $this->detailInfo->get('branch');
        
        return [
            'id' => $branch['id'] ?? $this->branchId,
            'code' => $branch['code'] ?? null,
            'name' => $branch['name'] ?? 'N/A',
        ];
    }

    public static function toUnassignedDivisionSnapshot(): array
    {
        return [
            'id'   => 0,
            'name' => 'Unassigned',
        ];
    }

    public static function toUnassignedBranchSnapshot(): array
    {
        return [
            'id'   => 0,
            'name' => 'Unassigned',
        ];
    }

    public function toWorkScheduleSnapshot(): array
    {
        $branch = $this->detailInfo->get('branch') ?? [];

        return $branch['work_pattern'] ?? [];
    }

    private function getDayOfWeek(string $date): int
    {
        return (int) date('N', strtotime($date));
    }

    public function toDailyScheduleSnapshot(string $date): array
    {
        $workPattern = $this->toWorkScheduleSnapshot();
        $workingDays = $workPattern['working_days'] ?? [];
        $dayIndex = $this->getDayOfWeek($date);

        // 1. Ambil semua nilai 'day_of_week' menjadi array flat [1, 2, 3, 4, 5, 6, 7]
        // 2. Cari posisi index (key) di mana nilai dayIndex berada
        $key = array_search($dayIndex, array_column($workingDays, 'day_of_week'));

        // 3. Ambil datanya berdasarkan key yang ditemukan, atau default jika false
        $schedule = ($key !== false) ? $workingDays[$key] : [
            'day_of_week' => $dayIndex,
            'shift' => null
        ];

        return [
            'id' => $workPattern['id'] ?? 0,
            'name' => $workPattern['name'] ?? 'N/A',
            'working_days' => [
                $schedule
            ]
        ];
    }

    public function determineAttendanceStatus(string $date, string $clockInTime): array
    {
        // Ambil snapshot jadwal hari tersebut
        $dailySchedule = $this->toDailyScheduleSnapshot($date);

        // Ambil elemen pertama dari working_days (karena sudah difilter oleh toDailyScheduleSnapshot)
        $schedule = $dailySchedule['working_days'][0] ?? null;

        $startTime = $schedule['shift']['start_time'] ?? null;

        if ($startTime === null) {
            return ['is_late' => false];
        }

        return [
            'is_late' => strtotime($clockInTime) > strtotime($startTime)
        ];
    }

    /**
     * Update function
     */
    public function updateUsername(string $username): void
    {
        $this->username = $username;
    }

    public function updateNik(?string $nik): void
    {
        $this->nik = $nik;
    }

    public function updateEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function updateBirthDate(\App\Domain\Employee\ValueObject\DateFormat $date): void
    {
        $this->birthDate = $date->toString();
    }

    public function updateDateStart(\App\Domain\Employee\ValueObject\DateFormat $date): void
    {
        $this->dateStart = $date->toString();
    }

    public function updateDateResign(\App\Domain\Employee\ValueObject\DateFormat $date): void
    {
        $this->dateResign = $date->toString();
    }

    public function updateDivision(?int $divisionId): void
    {
        $this->divisionId = $divisionId;
    }

    public function updatePosition(?int $positionId): void
    {
        $this->positionId = $positionId;
    }

    public function updateEmployeeType(?int $employeeTypeId): void
    {
        $this->employeeTypeId = $employeeTypeId;
    }

    public function updateBranch(?int $branchId): void
    {
        $this->branchId = $branchId;
    }

    public function updateGrade(?int $gradeId): void
    {
        $this->gradeId = $gradeId;
    }

    public function updateContact(Contact $contact): void
    {
        $this->contact = $contact;
    }

    public function updateImage(Image $image): void
    {
        $this->image = $image;
    }
}
