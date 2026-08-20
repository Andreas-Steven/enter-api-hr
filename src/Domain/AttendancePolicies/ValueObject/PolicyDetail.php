<?php

declare(strict_types=1);

namespace App\Domain\AttendancePolicies\ValueObject;

use App\Domain\Shared\Enums\MissingPolicy;

use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;
use InvalidArgumentException;

/**
 * Immutable Value Object representing a single policy rule configuration.
 */
final class PolicyDetail
{
    private ?int $lateGraceIn;
    private ?int $earlyGraceOut;
    private ?int $minWorkHalfDay;
    private ?int $minWorkFullDay;
    private ?int $minOvertimeMinutes;
    private ?MissingPolicy $missingInPolicy; 
    private ?MissingPolicy $missingOutPolicy; 

    public function __construct(
        ?int $lateGraceIn = null,
        ?int $earlyGraceOut = null,
        ?int $minWorkHalfDay = null,
        ?int $minWorkFullDay = null,
        ?int $minOvertimeMinutes = null,
        ?MissingPolicy $missingInPolicy = null,
        ?MissingPolicy $missingOutPolicy = null
    ) {
        // Domain Guard Clauses untuk validasi rentang menit (0 - 1440)
        $this->validateMinutes($lateGraceIn, 'late_grace_in');
        $this->validateMinutes($earlyGraceOut, 'early_grace_out');
        $this->validateMinutes($minWorkHalfDay, 'min_work_half_day');
        $this->validateMinutes($minWorkFullDay, 'min_work_full_day');
        $this->validateMinutes($minOvertimeMinutes, 'min_overtime_minutes');

        $this->lateGraceIn = $lateGraceIn;
        $this->earlyGraceOut = $earlyGraceOut;
        $this->minWorkHalfDay = $minWorkHalfDay;
        $this->minWorkFullDay = $minWorkFullDay;
        $this->minOvertimeMinutes = $minOvertimeMinutes;
        $this->missingInPolicy = $missingInPolicy;
        $this->missingOutPolicy = $missingOutPolicy;
    }

    /**
     * Validasi rentang menit (0 - 1440)
     */
    private function validateMinutes(?int $value, string $fieldName): void
    {
        if ($value !== null && ($value < 0 || $value > 1440)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'attendance_policy.invalid_minutes',
                    params: ['resource' => $fieldName],
                    domain: 'app',
                )
            );
        }
    }

    /**
     * Named constructor untuk membuat objek dari data mentah array/JSON
     */
    public static function fromArray(array $data): self
    {
        $missingIn = self::resolveAndValidateEnum($data['missing_in_policy'] ?? null, 'missing_in_policy');
        $missingOut = self::resolveAndValidateEnum($data['missing_out_policy'] ?? null, 'missing_out_policy');

        return new self(
            $data['late_grace_in'] ?? null,
            $data['early_grace_out'] ?? null,
            $data['min_work_half_day'] ?? null,
            $data['min_work_full_day'] ?? null,
            $data['min_overtime_minutes'] ?? null,
            $missingIn,
            $missingOut
        );
    }

    private static function resolveAndValidateEnum(?string $value, string $fieldName): ?MissingPolicy
    {
        if ($value === null || $value === '') {
            return null;
        }

        $enumValue = MissingPolicy::tryFrom($value);

        if ($enumValue === null) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'attendance_policy.invalid_policy_value',
                    params: ['resource' => $fieldName],
                    domain: 'app',
                )
            );
        }

        return $enumValue;
    }

    /**
     * Named constructor untuk membuat objek langsung dari string JSON database
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        
        if (!is_array($data)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'attendance_policy.invalid_format',
                    params: ['resource' => 'Policy Detail'],
                    domain: 'app',
                )
            );
        }

        return self::fromArray($data);
    }

    /**
     * Mengembalikan data ke bentuk array asli untuk disimpan ke DB JSONB
     */
    public function toArray(): array
    {
        return [
            'late_grace_in' => $this->lateGraceIn,
            'early_grace_out' => $this->earlyGraceOut,
            'min_work_half_day' => $this->minWorkHalfDay,
            'min_work_full_day' => $this->minWorkFullDay,
            'min_overtime_minutes' => $this->minOvertimeMinutes,
            'missing_in_policy' => $this->missingInPolicy,
            'missing_out_policy' => $this->missingOutPolicy,
        ];
    }

    // --- Getters ---
    public function getLateGraceIn(): ?int
    {
        return $this->lateGraceIn;
    }

    public function getEarlyGraceOut(): ?int
    {
        return $this->earlyGraceOut;
    }

    public function getMinWorkHalfDay(): ?int
    {
        return $this->minWorkHalfDay;
    }

    public function getMinWorkFullDay(): ?int
    {
        return $this->minWorkFullDay;
    }

    public function getMinOvertimeMinutes(): ?int
    {
        return $this->minOvertimeMinutes;
    }

    public function getMissingInPolicy(): ?MissingPolicy
    {
        return $this->missingInPolicy;
    }

    public function getMissingOutPolicy(): ?MissingPolicy
    {
        return $this->missingOutPolicy;
    }
}