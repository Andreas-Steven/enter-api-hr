<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Unified treatment options for AttendanceReview resolution.
 *
 * Satu enum yang gabung semua treatment option dari 3 source enum
 * (MissingPolicy, DeficitTreatment, ExcessTreatment) dengan label dan
 * description yang jelas. Client bisa lihat opsi apa yang valid untuk
 * resolved_treatment dan apa maksudnya.
 *
 * Mapping per reason:
 * - MISSING_SEND_TO_REVIEW → AUTO_CLOSE_BY_RULE, IGNORE_MISSING_EVENT, TREAT_AS_ABSENT
 * - DEFICIT_MANUAL_REVIEW  → TREAT_AS_ABSENT, USE_EMPLOYEE_LEAVE
 * - EXCESS_REQUIRE_APPROVAL → COUNT_AS_OVERTIME, IGNORE_EXCESS, NO_OVERTIME
 */
enum ReviewTreatment: string
{
    // MissingPolicy treatments
    case AUTO_CLOSE_BY_RULE = 'AUTO_CLOSE_BY_RULE';
    case IGNORE_MISSING_EVENT = 'IGNORE_MISSING_EVENT';
    case TREAT_AS_ABSENT = 'TREAT_AS_ABSENT';

    // DeficitTreatment treatments
    case USE_EMPLOYEE_LEAVE = 'USE_EMPLOYEE_LEAVE';

    // ExcessTreatment treatments
    case COUNT_AS_OVERTIME = 'COUNT_AS_OVERTIME';
    case IGNORE_EXCESS = 'IGNORE_EXCESS';
    case NO_OVERTIME = 'NO_OVERTIME';

    public function label(): string
    {
        return match ($this) {
            self::AUTO_CLOSE_BY_RULE => 'Auto Close by Rule',
            self::IGNORE_MISSING_EVENT => 'Ignore Missing Event',
            self::TREAT_AS_ABSENT => 'Treat as Absent',
            self::USE_EMPLOYEE_LEAVE => 'Use Employee Leave',
            self::COUNT_AS_OVERTIME => 'Count as Overtime',
            self::IGNORE_EXCESS => 'Ignore Excess',
            self::NO_OVERTIME => 'No Overtime',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::AUTO_CLOSE_BY_RULE => 'Tutup otomatis sesuai aturan shift, tidak ada penalti',
            self::IGNORE_MISSING_EVENT => 'Abaikan kejadian missing in/out, anggap tidak terjadi',
            self::TREAT_AS_ABSENT => 'Tandai karyawan sebagai tidak hadir (absen)',
            self::USE_EMPLOYEE_LEAVE => 'Gunakan saldo cuti karyawan untuk menutup defisit',
            self::COUNT_AS_OVERTIME => 'Hitung kelebihan jam sebagai lembur (overtime)',
            self::IGNORE_EXCESS => 'Abaikan kelebihan jam, tidak dihitung lembur',
            self::NO_OVERTIME => 'Tidak ada pembayaran lembur, kelebihan jam diabaikan',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Returns the treatment options available for a given review reason.
     *
     * Mapping explicit per anomali:
     * - MISSING_SEND_TO_REVIEW: karyawan missing in/out → opsi resolve
     * - DEFICIT_MANUAL_REVIEW: karyawan kurang jam kerja → opsi resolve
     * - EXCESS_REQUIRE_APPROVAL: karyawan kelebihan jam → opsi resolve
     *
     * @return list<self>
     */
    public static function optionsForReason(ReviewReason $reason): array
    {
        return match ($reason) {
            ReviewReason::MISSING_SEND_TO_REVIEW => [
                self::AUTO_CLOSE_BY_RULE,
                self::IGNORE_MISSING_EVENT,
                self::TREAT_AS_ABSENT,
            ],
            ReviewReason::DEFICIT_MANUAL_REVIEW => [
                self::TREAT_AS_ABSENT,
                self::USE_EMPLOYEE_LEAVE,
            ],
            ReviewReason::EXCESS_REQUIRE_APPROVAL => [
                self::COUNT_AS_OVERTIME,
                self::IGNORE_EXCESS,
                self::NO_OVERTIME,
            ],
        };
    }

    /**
     * Returns the treatment options for a reason as array of
     * [value, label, description] for API response / client consumption.
     *
     * @return list<array{value: string, label: string, description: string}>
     */
    public static function detailedOptionsForReason(ReviewReason $reason): array
    {
        return array_map(
            static fn(self $treatment): array => [
                'value' => $treatment->value,
                'label' => $treatment->label(),
                'description' => $treatment->description(),
            ],
            self::optionsForReason($reason),
        );
    }
}
