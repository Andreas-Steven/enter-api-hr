<?php

declare(strict_types=1);

namespace App\Domain\EmployeeDebtInstallment\Service;

// Domain Layer
use App\Domain\EmployeeDebtInstallment\Entity\EmployeeDebtInstallment;
use App\Domain\EmployeeDebtInstallment\Repository\EmployeeDebtInstallmentRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\ConflictException;

/**
 * EmployeeDebtInstallment Domain Service
 *
 * Pure business logic and domain rules untuk aggregate cicilan hutang.
 */
final class EmployeeDebtInstallmentDomainService
{
    use DomainValidator;

    public function __construct(
        private EmployeeDebtInstallmentRepositoryInterface $repository
    ) {}

    /**
     * Validasi saat create manual installment:
     * - employee_debt_id wajib > 0
     * - sequence wajib unik per employee_debt_id
     * - amount wajib > 0
     */
    public function guardCreate(
        int $employeeDebtId,
        int $sequence,
        float $amount,
    ): void {
        if ($employeeDebtId < 1) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'validation.required',
                    domain: 'validation',
                    params: ['field' => 'employee_debt_id']
                )
            );
        }

        if ($sequence < 1) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'validation.min_value',
                    domain: 'validation',
                    params: ['field' => 'sequence', 'min' => 1]
                )
            );
        }

        if ($amount <= 0) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'validation.min_value',
                    domain: 'validation',
                    params: ['field' => 'amount', 'min' => 0]
                )
            );
        }

        $this->guardSequenceUnique(
            employeeDebtId: $employeeDebtId,
            sequence: $sequence,
            excludeId: null
        );
    }

    /**
     * Validasi saat update installment:
     * - Tidak boleh ubah sequence ke nilai yang sudah dipakai sibling
     * - Tidak boleh ubah employee_debt_id jika installment sudah paid
     */
    public function guardUpdate(
        EmployeeDebtInstallment $installment,
        ?int $newSequence,
        ?int $newEmployeeDebtId,
    ): void {
        if ($installment->isPaid() && $newEmployeeDebtId !== null && $newEmployeeDebtId !== $installment->getEmployeeDebtId()) {
            throw new ConflictException(
                translate: Message::create(
                    key: 'employee_debt_installment.cannot_move_paid',
                    domain: 'validation',
                    params: ['id' => $installment->getId()]
                )
            );
        }

        if ($newSequence !== null && $newSequence !== $installment->getSequence()) {
            $this->guardSequenceUnique(
                employeeDebtId: $installment->getEmployeeDebtId(),
                sequence: $newSequence,
                excludeId: $installment->getId()
            );
        }
    }

    /**
     * Cek sequence unik per employee_debt_id (tidak boleh ada 2 cicilan
     * dengan sequence sama pada satu hutang).
     */
    public function guardSequenceUnique(int $employeeDebtId, int $sequence, ?int $excludeId): void
    {
        $siblings = $this->repository->findByEmployeeDebtId(
            employeeDebtId: $employeeDebtId,
            status: null
        );

        foreach ($siblings as $sibling) {
            if ($sibling->getSequence() === $sequence && $sibling->getId() !== $excludeId) {
                throw new ConflictException(
                    translate: Message::create(
                        key: 'employee_debt_installment.sequence_taken',
                        domain: 'validation',
                        params: [
                            'employee_debt_id' => $employeeDebtId,
                            'sequence' => $sequence,
                        ]
                    )
                );
            }
        }
    }

    /**
     * Validasi saat menandai installment sebagai paid:
     * - Tidak boleh paid dua kali
     * - payroll_deduction_id (kalau disertakan) wajib > 0
     */
    public function guardMarkAsPaid(
        EmployeeDebtInstallment $installment,
        ?int $payrollDeductionId = null,
    ): void {
        if ($installment->isPaid()) {
            throw new ConflictException(
                translate: Message::create(
                    key: 'employee_debt_installment.already_paid',
                    domain: 'validation',
                    params: ['id' => $installment->getId()]
                )
            );
        }

        if ($payrollDeductionId !== null && $payrollDeductionId < 1) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'validation.min_value',
                    domain: 'validation',
                    params: ['field' => 'payroll_deduction_id', 'min' => 1]
                )
            );
        }
    }
}
