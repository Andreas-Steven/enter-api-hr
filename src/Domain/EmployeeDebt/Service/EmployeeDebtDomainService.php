<?php

declare(strict_types=1);

namespace App\Domain\EmployeeDebt\Service;

// Domain Layer
use App\Domain\EmployeeDebt\Entity\EmployeeDebt;
use App\Domain\EmployeeDebt\Repository\EmployeeDebtRepositoryInterface;
use App\Domain\EmployeeDebtInstallment\Entity\EmployeeDebtInstallment;
use App\Domain\EmployeeDebtInstallment\Repository\EmployeeDebtInstallmentRepositoryInterface;
use App\Domain\PayrollDeduction\Entity\PayrollDeduction;
use App\Domain\PayrollDeduction\Repository\PayrollDeductionRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Application Layer
use App\Application\EmployeeDebt\Dto\EmployeeDebtDetailInfo;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;
use App\Domain\Shared\Enums\PaymentTerm;

/**
 * EmployeeDebt Domain Service
 *
 * Pure business logic and domain rules
 */
final class EmployeeDebtDomainService
{
    use DomainValidator;

    public function __construct(
        private EmployeeDebtRepositoryInterface $repository,
        private EmployeeDebtInstallmentRepositoryInterface $installmentRepository,
        private PayrollDeductionRepositoryInterface $payrollDeductionRepository,
    ) {}

    public function guardDebtCreation(
        EmployeeDebtDetailInfo $debtInfo,
        int $paymentTerm,
        ?string $paymentMethod,
        int $installmentCount,
        ?string $firstPaymentDate,
    ): int {
        $createsDebt = (int) ($debtInfo->deduction_type['creates_debt'] ?? 0);

        // Auto-override payment_term to FULL if deduction type doesn't create debt
        if ($createsDebt !== 1 && $paymentTerm === PaymentTerm::INSTALLMENT) {
            $paymentTerm = PaymentTerm::FULL;
        }

        if ($paymentTerm === PaymentTerm::INSTALLMENT) {
            if (empty($paymentMethod)) {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'employee_debt.payment_method_required',
                        params: [],
                        domain: 'validation'
                    )
                );
            }

            if ($installmentCount < 2) {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'employee_debt.installment_count_invalid',
                        params: ['min' => 2],
                        domain: 'validation'
                    )
                );
            }

            if (empty($firstPaymentDate)) {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'employee_debt.first_payment_date_required',
                        params: [],
                        domain: 'validation'
                    )
                );
            }
        }

        return $paymentTerm;
    }

    public function generateInstallmentSchedule(
        EmployeeDebt $debt,
        EmployeeDebtDetailInfo $debtInfo,
    ): void {
        $count = $debt->getInstallmentCount();
        $firstDueDate = $debt->getFirstPaymentDate();
        $baseAmount = $debt->getInstallmentAmount() > 0
            ? $debt->getInstallmentAmount()
            : ($count > 0 ? $debt->getAmount() / $count : $debt->getAmount());

        if ($count < 1 || $firstDueDate === null) {
            return;
        }

        $remaining = $debt->getAmount();

        for ($sequence = 1; $sequence <= $count; $sequence++) {
            $dueDate = (new \DateTimeImmutable($firstDueDate))
                ->modify('+' . ($sequence - 1) . ' month')
                ->format('Y-m-d');

            $amount = $sequence === $count
                ? max(0, $remaining)
                : round($baseAmount, 2);

            $remaining = round($remaining - $amount, 2);
            if ($remaining < 0) {
                $remaining = 0;
            }

            $installment = EmployeeDebtInstallment::create(
                name: sprintf('INS-%d-%d', $debt->getId(), $sequence),
                employeeDebtId: $debt->getId(),
                payrollDeductionId: null,
                sequence: $sequence,
                dueDate: $dueDate,
                amount: (float) $amount,
                status: ResourceStatus::active(),
                detailInfo: DetailInfo::fromArray([
                    'employee_debt' => [
                        'id' => $debt->getId(),
                        'name' => $debt->getName(),
                    ],
                    'payroll_deduction_type' => $debtInfo->deduction_type,
                ]),
            );

            $this->installmentRepository->insert($installment);
        }
    }

    /**
     * Auto-generate PayrollDeduction records saat debt dibuat dengan
     * payment_method = payroll_deduction.
     *
     * - INSTALLMENT: buat PayrollDeduction per installment, period dari due_date.
     * - FULL: buat 1 PayrollDeduction untuk full amount, period dari first_payment_date.
     *
     * Status = DRAFT karena periode gaji belum tiba.
     * Installment.payrollDeductionId di-link ke PayrollDeduction yang dibuat.
     */
    public function generatePayrollDeductionSchedule(
        EmployeeDebt $debt,
        EmployeeDebtDetailInfo $debtInfo,
    ): void {
        if ($debt->getPaymentMethod() !== 'payroll_deduction') {
            return;
        }

        if ($debt->getPaymentTerm() === PaymentTerm::FULL) {
            $this->generateSinglePayrollDeduction($debt, $debtInfo);
            return;
        }

        $installments = $this->installmentRepository->findByEmployeeDebtId(
            employeeDebtId: $debt->getId(),
            status: null,
        );

        foreach ($installments as $installment) {
            $period = substr($installment->getDueDate(), 0, 7);

            $deduction = PayrollDeduction::create(
                name: sprintf('DED-%d-%s-INS%d', $debt->getEmployeeId(), $period, $installment->getId()),
                employeeId: $debt->getEmployeeId(),
                deductionTypeId: $debt->getDeductionTypeId(),
                attendanceReportId: null,
                employeeDebtId: $debt->getId(),
                employeeDebtInstallmentId: $installment->getId(),
                period: $period,
                amount: $installment->getAmount(),
                description: sprintf(
                    'Auto-generated installment #%d for %s (due %s)',
                    $installment->getSequence(),
                    $debt->getName(),
                    $installment->getDueDate(),
                ),
                status: ResourceStatus::draft(),
                detailInfo: DetailInfo::fromArray([
                    'employee' => $debtInfo->employee,
                    'payroll_deduction_type' => $debtInfo->deduction_type,
                    'employee_debt' => [
                        'id' => $debt->getId(),
                        'name' => $debt->getName(),
                    ],
                    'employee_debt_installment' => [
                        'id' => $installment->getId(),
                        'name' => $installment->getName(),
                        'sequence' => $installment->getSequence(),
                    ],
                ]),
            );

            $deduction = $this->payrollDeductionRepository->insert($deduction);

            $installment->linkPayrollDeduction($deduction->getId());
            $this->installmentRepository->update($installment);
        }
    }

    private function generateSinglePayrollDeduction(
        EmployeeDebt $debt,
        EmployeeDebtDetailInfo $debtInfo,
    ): void {
        $firstPaymentDate = $debt->getFirstPaymentDate();
        if ($firstPaymentDate === null) {
            return;
        }

        $period = substr($firstPaymentDate, 0, 7);

        $deduction = PayrollDeduction::create(
            name: sprintf('DED-%d-%s', $debt->getEmployeeId(), $period),
            employeeId: $debt->getEmployeeId(),
            deductionTypeId: $debt->getDeductionTypeId(),
            attendanceReportId: null,
            employeeDebtId: $debt->getId(),
            employeeDebtInstallmentId: null,
            period: $period,
            amount: $debt->getAmount(),
            description: sprintf(
                'Auto-generated full payment for %s (due %s)',
                $debt->getName(),
                $firstPaymentDate,
            ),
            status: ResourceStatus::draft(),
            detailInfo: DetailInfo::fromArray([
                'employee' => $debtInfo->employee,
                'payroll_deduction_type' => $debtInfo->deduction_type,
                'employee_debt' => [
                    'id' => $debt->getId(),
                    'name' => $debt->getName(),
                ],
            ]),
        );

        $this->payrollDeductionRepository->insert($deduction);
    }
}