<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum ApprovalStatus: int
{
    case DEFAULT = 0;
    case APPROVED = 1;
    case REJECTED = 2;
    
    public function getLabel(): string
    {
        return match($this) {
            self::DEFAULT => 'Default',
            self::REJECTED => 'Rejected',
            self::APPROVED => 'Approved',
        };
    }

    /**
     * Mendapatkan daftar status asal yang diperbolehkan untuk status ini
     */
    public function getAllowedSources(): array
    {
        return match($this) {
            self::APPROVED => [
                self::DEFAULT, 
                self::REJECTED, 
                self::APPROVED
            ],
            self::REJECTED => [
                self::DEFAULT
            ],
            self::DEFAULT => [], // Biasanya tidak ada transisi kembali ke Default
        };
    }

    /**
     * Validasi apakah status saat ini bisa berubah ke status target
     */
    public function canTransitionFrom(self $currentStatus): bool
    {
        return in_array($currentStatus, $this->getAllowedSources(), true);
    }
}