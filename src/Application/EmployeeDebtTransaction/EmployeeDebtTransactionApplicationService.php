<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebtTransaction;

// Application Layer
use App\Application\EmployeeDebtTransaction\Command\CreateEmployeeDebtTransactionCommand;
use App\Application\EmployeeDebtTransaction\Command\UpdateEmployeeDebtTransactionCommand;
use App\Application\EmployeeDebtTransaction\Dto\EmployeeDebtTransactionResponse;
use App\Application\EmployeeDebtTransaction\EmployeeDebtTransactionDetailInfoFactory;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\EmployeeDebt\Entity\EmployeeDebt;
use App\Domain\EmployeeDebt\Repository\EmployeeDebtRepositoryInterface;
use App\Domain\EmployeeDebtTransaction\Entity\EmployeeDebtTransaction;
use App\Domain\EmployeeDebtTransaction\Repository\EmployeeDebtTransactionRepositoryInterface;
use App\Domain\EmployeeDebtTransaction\Service\EmployeeDebtTransactionDomainService;
use App\Domain\EmployeeDebtTransaction\ValueObject\Name;
use App\Domain\Shared\Enums\TransactionType;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * EmployeeDebtTransaction Application Service
 *
 * Orchestrates use cases and coordinates domain & infrastructure.
 *
 * Selain CRUD generik, service ini menjadi ORCHESTRATOR cascade ke
 * EmployeeDebt: setiap transaksi pembayaran (payment/payroll) akan
 * memperbarui debt.paid_amount & debt.remaining_amount. Delete/reversal
 * membalik efek tersebut.
 */
final class EmployeeDebtTransactionApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private EmployeeDebtTransactionDetailInfoFactory $transactionInfoFactory,
        private EmployeeDebtTransactionRepositoryInterface $repository,
        private EmployeeDebtRepositoryInterface $employeeDebtRepository,
        private EmployeeDebtTransactionDomainService $domainService,
    ) {
    }

    public function getResource(): string
    {
        return EmployeeDebtTransaction::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): EmployeeDebtTransaction
    {
        $data = $this->repository->findById(
            id: $id,
            status: $status
        );

        if ($data === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => $this->getResource(),
                        'field' => 'id',
                        'value' => $id
                    ]
                )
            );
        }

        return $data;
    }

    private function getDebtById(int $id): EmployeeDebt
    {
        $debt = $this->employeeDebtRepository->findById(
            id: $id,
            status: null
        );

        if ($debt === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => 'EmployeeDebt',
                        'field' => 'id',
                        'value' => $id
                    ]
                )
            );
        }

        return $debt;
    }

    public function list(SearchCriteria $criteria): PaginatedResult
    {
        return $this->repository->list(
            criteria: $criteria
        );
    }

    public function view(int $id): EmployeeDebtTransactionResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );

        return EmployeeDebtTransactionResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreateEmployeeDebtTransactionCommand $command): EmployeeDebtTransactionResponse
    {
        $this->domainService->guardCreate(
            transactionType: $command->transactionType,
            paymentSource: $command->paymentSource,
            payrollDeductionId: $command->payrollDeductionId,
            payrollPeriod: $command->payrollPeriod,
            amount: $command->amount,
        );

        $info = $this->transactionInfoFactory->buildFromIds(
            employeeId: $command->employeeId,
            employeeDebtId: $command->employeeDebtId,
            payrollDeductionId: $command->payrollDeductionId,
        );

        $detailInfo = $this->detailInfoFactory
            ->create(detailInfo: $info->toArray())
            ->build();

        $data = EmployeeDebtTransaction::create(
            name: Name::generate($command->employeeId)->toString(),
            employeeDebtId: $command->employeeDebtId,
            employeeId: $command->employeeId,
            transactionType: $command->transactionType,
            date: $command->date,
            amount: $command->amount,
            paymentSource: $command->paymentSource,
            payrollDeductionId: $command->payrollDeductionId,
            payrollPeriod: $command->payrollPeriod,
            note: $command->note,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        $data = $this->repository->insert(entity: $data);

        // Cascade: update EmployeeDebt paid_amount/remaining_amount
        $this->applyTransactionToDebt($data);

        return EmployeeDebtTransactionResponse::fromEntity(
            entity: $data
        );
    }

    public function update(int $id, UpdateEmployeeDebtTransactionCommand $command): EmployeeDebtTransactionResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );

        $this->repository->verifyLockVersion(
            entity: $data,
            version: $command->lockVersion ?? null
        );

        $newStatus = ResourceStatus::tryFrom($command->status);

        $hasFieldChanges = $data->hasFieldChanges(
            data: (array) $command,
            removeNulls: true
        );

        $data->guardAgainstInvalidTransition(
            hasFieldChanges: $hasFieldChanges,
            newStatus: $newStatus
        );

        // Reverse effect of old transaction on debt before applying new values
        $this->reverseTransactionFromDebt($data);

        if ($command->employeeDebtId !== null) {
            $data->setEmployeeDebtId(employeeDebtId: $command->employeeDebtId);
        }

        if ($command->employeeId !== null) {
            $data->setEmployeeId(employeeId: $command->employeeId);
        }

        if ($command->transactionType !== null) {
            $data->setTransactionType(transactionType: $command->transactionType);
        }

        if ($command->date !== null) {
            $data->setDate(date: $command->date);
        }

        if ($command->amount !== null) {
            $data->setAmount(amount: $command->amount);
        }

        if ($command->paymentSource !== null) {
            $data->setPaymentSource(paymentSource: $command->paymentSource);
        }

        if ($command->payrollDeductionId !== null) {
            $data->setPayrollDeductionId(payrollDeductionId: $command->payrollDeductionId);
        }

        if ($command->payrollPeriod !== null) {
            $data->setPayrollPeriod(payrollPeriod: $command->payrollPeriod);
        }

        if ($command->note !== null) {
            $data->setNote(note: $command->note);
        }

        $this->domainService->guardUpdate(
            entity: $data,
            transactionType: $command->transactionType,
            paymentSource: $command->paymentSource,
            payrollDeductionId: $command->payrollDeductionId,
            payrollPeriod: $command->payrollPeriod,
            amount: $command->amount,
        );

        $data->applyStatus(newStatus: $newStatus);

        $info = $this->transactionInfoFactory->buildFromIds(
            employeeId: $data->getEmployeeId(),
            employeeDebtId: $data->getEmployeeDebtId(),
            payrollDeductionId: $data->getPayrollDeductionId(),
        );

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: $info->toArray(),
            )
            ->build();

        $data->updateDetailInfo(detailInfo: $detailInfo);

        $data = $this->repository->update(entity: $data);

        // Re-apply effect of updated transaction on debt
        $this->applyTransactionToDebt($data);

        return EmployeeDebtTransactionResponse::fromEntity(
            entity: $data
        );
    }

    public function delete(int $id, ?int $lockVersion = null): EmployeeDebtTransactionResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );

        $this->repository->verifyLockVersion(
            entity: $data,
            version: $lockVersion,
        );

        $this->domainService->guardPermission(
            authorizer: $this->auth,
            permission: 'employee-debt-transaction.delete',
            resource: $this->getResource(),
            id: $id,
        );

        $this->domainService->ensureDeletable(
            entity: $data,
            resource: $this->getResource(),
        );

        // Reverse effect of transaction on debt before deleting
        $this->reverseTransactionFromDebt($data);

        $detailInfo = $this->detailInfoFactory
            ->delete(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->build();

        $data->updateDetailInfo(detailInfo: $detailInfo);

        return EmployeeDebtTransactionResponse::fromEntity(
            entity: $this->repository->delete(entity: $data)
        );
    }

    public function restore(int $id): EmployeeDebtTransactionResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: ResourceStatus::deleted()->value()
        );

        $data->guardAgainstInvalidTransition(
            hasFieldChanges: false,
            newStatus: ResourceStatus::restored()
        );

        $data->markAsRestored();

        $detailInfo = $this->detailInfoFactory
            ->restore(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->build();

        $data->updateDetailInfo(detailInfo: $detailInfo);

        $restoredData = $this->repository->restore(
            id: $data->getId()
        );

        // Re-apply effect of restored transaction on debt
        $this->applyTransactionToDebt($restoredData);

        return EmployeeDebtTransactionResponse::fromEntity(
            entity: $restoredData
        );
    }

    /**
     * Apply payment dari PayrollDeduction ke EmployeeDebt.
     *
     * Dipanggil oleh PayrollDeductionApplicationService saat membuat potongan
     * gaji yang terkait EmployeeDebt. Membuat transaksi ledger dengan
     * transaction_type=payment, payment_source=payroll.
     *
     * Note: parameter $installmentId di-keep untuk backward compatibility
     * dengan PayrollDeductionApplicationService, namun tidak digunakan karena
     * modul EmployeeDebtInstallment sudah dihapus.
     */
    public function applyPaymentFromPayrollDeduction(
        int $employeeDebtId,
        ?int $installmentId,
        int $payrollDeductionId,
        int $payrollPeriod,
        float $amount,
        string $transactionDate,
        ?string $note = null,
    ): EmployeeDebtTransactionResponse {
        $debt = $this->getDebtById($employeeDebtId);

        $info = $this->transactionInfoFactory->buildFromIds(
            employeeId: $debt->getEmployeeId(),
            employeeDebtId: $employeeDebtId,
            payrollDeductionId: $payrollDeductionId,
        );

        $detailInfo = $this->detailInfoFactory
            ->create(detailInfo: $info->toArray())
            ->build();

        $data = EmployeeDebtTransaction::create(
            name: Name::generate($debt->getEmployeeId())->toString(),
            employeeDebtId: $employeeDebtId,
            employeeId: $debt->getEmployeeId(),
            transactionType: TransactionType::PAYMENT,
            date: $transactionDate,
            amount: $amount,
            paymentSource: \App\Domain\Shared\Enums\PaymentSource::PAYROLL,
            payrollDeductionId: $payrollDeductionId,
            payrollPeriod: $payrollPeriod,
            note: $note,
            status: ResourceStatus::from(\App\Shared\Enums\RecordStatus::DRAFT->value),
            detailInfo: $detailInfo
        );

        $data = $this->repository->insert(entity: $data);

        // Cascade: update EmployeeDebt paid_amount/remaining_amount
        $this->applyTransactionToDebt($data);

        return EmployeeDebtTransactionResponse::fromEntity(
            entity: $data
        );
    }

    /**
     * Apply transaction effect to EmployeeDebt:
     * - payment: debt.recordPayment(amount)
     * - adjustment: recordPayment if positive, reversePayment if negative
     */
    private function applyTransactionToDebt(EmployeeDebtTransaction $transaction): void
    {
        $debt = $this->getDebtById($transaction->getEmployeeDebtId());
        $type = $transaction->getTransactionType();
        $amount = $transaction->getAmount();

        if (TransactionType::isInflow($type)) {
            $debt->recordPayment($amount);
        } elseif (TransactionType::isAdjustment($type)) {
            if ($amount > 0) {
                $debt->recordPayment($amount);
            } else {
                $debt->reversePayment(abs($amount));
            }
        }

        $this->employeeDebtRepository->update(entity: $debt);
    }

    /**
     * Reverse transaction effect from EmployeeDebt (sebelum update/delete).
     */
    private function reverseTransactionFromDebt(EmployeeDebtTransaction $transaction): void
    {
        $debt = $this->getDebtById($transaction->getEmployeeDebtId());
        $type = $transaction->getTransactionType();
        $amount = $transaction->getAmount();

        if (TransactionType::isInflow($type)) {
            $debt->reversePayment($amount);
        } elseif (TransactionType::isAdjustment($type)) {
            if ($amount > 0) {
                $debt->reversePayment($amount);
            } else {
                $debt->recordPayment(abs($amount));
            }
        }

        $this->employeeDebtRepository->update(entity: $debt);
    }
}
