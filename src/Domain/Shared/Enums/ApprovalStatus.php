<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum ApprovalStatus: int
{
    case PENDING = 0;
    case APPROVED = 1;
    case REJECTED = 2;
    
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
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
                self::PENDING, 
                self::REJECTED, 
                self::APPROVED
            ],
            self::REJECTED => [
                self::PENDING
            ],
            self::PENDING => [], // Biasanya tidak ada transisi kembali ke Pending
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