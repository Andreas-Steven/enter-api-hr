<?php

declare(strict_types=1);

namespace App\Application\EmployeeLeave\Command;

/**
 * Command for appending a single adjustment transaction to an existing
 * EmployeeLeave record via PUT /v1/employee-leave/{id}/adjustment.
 *
 * @param int    $amount           absolute amount (always positive)
 * @param string $adjustmentType   direction: "plus" or "minus"
 * @param string $adjustmentLeaveType  reason category (REWARD, MANUAL, etc)
 * @param string|null $note        free-form description
 * @param int|null $lockVersion    optimistic locking version
 */
final readonly class AdjustEmployeeLeaveCommand
{
    public function __construct(
        public int $id,
        public int $amount,
        public string $adjustmentType,
        public string $adjustmentLeaveType,
        public ?string $note,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        int $amount,
        string $adjustmentType,
        string $adjustmentLeaveType,
        ?string $note = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            amount: $amount,
            adjustmentType: $adjustmentType,
            adjustmentLeaveType: $adjustmentLeaveType,
            note: $note,
            lockVersion: $lockVersion,
        );
    }
}
