<?php

declare(strict_types=1);

namespace App\Domain\EmployeeDebt\Service;

// Domain Layer
use App\Domain\EmployeeDebt\Entity\EmployeeDebt;
use App\Domain\EmployeeDebt\Repository\EmployeeDebtRepositoryInterface;
use App\Domain\EmployeeDebtInstallment\Entity\EmployeeDebtInstallment;
use App\Domain\EmployeeDebtInstallment\Repository\EmployeeDebtInstallmentRepositoryInterface;
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
}