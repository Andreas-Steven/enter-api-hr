<?php

declare(strict_types=1);

namespace App\Domain\PayrollReport\ValueObject;

use App\Domain\Shared\Enums\PayrollNetType;

/**
 * Payroll net result (the top-level `payroll_net` JSONB block).
 *
 * Records the net balance between overtime earnings and deductions:
 *
 *   amount = total_overtime - total_deduction
 *
 * - POSITIVE: overtime > deduction → sisa ditambah ke gaji
 * - NEGATIVE: deduction > overtime → gaji dipotong
 */
final readonly class PayrollNet
{
    private function __construct(
        private int $totalOvertime,
        private int $totalDeduction,
        private PayrollNetType $type,
        private int $amount,
    ) {}

    public static function empty(): self
    {
        return new self(0, 0, PayrollNetType::POSITIVE, 0);
    }

    public static function fromArray(array $data): self
    {
        $amount = (int) ($data['amount'] ?? 0);

        return new self(
            (int) ($data['total_overtime'] ?? 0),
            (int) ($data['total_deduction'] ?? 0),
            PayrollNetType::from((string) ($data['type'] ?? PayrollNetType::POSITIVE->value)),
            $amount,
        );
    }

    public static function fromValues(int $totalOvertime, int $totalDeduction): self
    {
        $amount = $totalOvertime - $totalDeduction;

        return new self(
            $totalOvertime,
            $totalDeduction,
            PayrollNetType::fromAmount($amount),
            $amount,
        );
    }

    public function getTotalOvertime(): int
    {
        return $this->totalOvertime;
    }

    public function getTotalDeduction(): int
    {
        return $this->totalDeduction;
    }

    public function getType(): PayrollNetType
    {
        return $this->type;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'total_overtime' => $this->totalOvertime,
            'total_deduction' => $this->totalDeduction,
            'type' => $this->type->value,
            'amount' => $this->amount,
        ];
    }
}
