<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebtTransaction;

// Application Layer
use App\Application\EmployeeDebtTransaction\Command\CreateEmployeeDebtTransactionCommand;
use App\Application\EmployeeDebtTransaction\Command\UpdateEmployeeDebtTransactionCommand;
use App\Application\EmployeeDebtTransaction\Dto\EmployeeDebtTransactionResponse;
use App\Application\EmployeeDebtTransaction\EmployeeDebtTransactionDetailInfoFactory;
use App\Application\PayrollDeduction\PayrollDeductionDetailInfoFactory;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\EmployeeDebt\Entity\EmployeeDebt;
use App\Domain\EmployeeDebt\Repository\EmployeeDebtRepositoryInterface;
use App\Domain\EmployeeDebtTransaction\Entity\EmployeeDebtTransaction;
use App\Domain\EmployeeDebtTransaction\Repository\EmployeeDebtTransactionRepositoryInterface;
use App\Domain\EmployeeDebtTransaction\Service\EmployeeDebtTransactionDomainService;
use App\Domain\EmployeeDebtTransaction\ValueObject\Name;
use App\Domain\PayrollDeduction\Entity\PayrollDeduction;
use App\Domain\PayrollDeduction\Repository\PayrollDeductionRepositoryInterface;
use App\Domain\PayrollDeduction\Service\PayrollDeductionDomainService;
use App\Domain\PayrollDeduction\ValueObject\Name as DeductionName;
use App\Domain\PayrollDeduction\ValueObject\Note as DeductionNote;
use App\Domain\Shared\Enums\PaymentSource;
use App\Domain\Shared\Enums\TransactionType;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;
use App\Shared\Enums\RecordStatus;

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
        private PayrollDeductionRepositoryInterface $payrollDeductionRepository,
        private PayrollDeductionDomainService $payrollDeductionDomainService,
        private PayrollDeductionDetailInfoFactory $payrollDeductionInfoFactory,
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

    private function getDebtById(int $employeeDebtId): EmployeeDebt
    {
        $debt = $this->employeeDebtRepository->findById(
            id: $employeeDebtId,
            status: null
        );

        if ($debt === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => 'EmployeeDebt',
                        'field' => 'id',
                        'value' => $employeeDebtId
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
        // 1. Fetch debt & guard: Active (approval only required for payment)
        $debt = $this->getDebtById(
            employeeDebtId: $command->employeeDebtId
        );

        $this->domainService->guardDebtApprovedAndActive(
            debt: $debt,
            transactionType: $command->transactionType,
        );

        // 2. Derive employeeId dari debt (tidak dari payload)
        $employeeId = $debt->getEmployeeId();

        // 3. Auto-create PayrollDeduction jika payment_source=payroll dan
        //    payroll_deduction_id tidak dikirim (reverse flow). employee_id &
        //    deduction_type_id di-derive dari EmployeeDebt.
        $payrollDeductionId = $command->payrollDeductionId;
        if ($command->paymentSource === PaymentSource::PAYROLL && $payrollDeductionId === null && $command->payrollPeriod !== null) {
            $payrollDeductionId = $this->createPayrollDeductionForDebt(
                debt: $debt,
                period: $command->payrollPeriod,
                amount: $command->amount,
                note: $command->note,
            );
        }

        $this->domainService->guardCreate(
            transactionType: $command->transactionType,
            paymentSource: $command->paymentSource,
            payrollDeductionId: $payrollDeductionId,
            payrollPeriod: $command->payrollPeriod,
            amount: $command->amount,
        );

        $info = $this->transactionInfoFactory->buildFromIds(
            employeeId: $employeeId,
            employeeDebtId: $command->employeeDebtId,
            payrollDeductionId: $payrollDeductionId,
        );

        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: $info->toArray()
            )->build();

        $data = EmployeeDebtTransaction::create(
            name: Name::generate($employeeId)->toString(),
            employeeDebtId: $command->employeeDebtId,
            employeeId: $employeeId,
            transactionType: $command->transactionType,
            date: $command->date,
            amount: $command->amount,
            paymentSource: $command->paymentSource,
            payrollDeductionId: $payrollDeductionId,
            payrollPeriod: $command->payrollPeriod,
            note: $command->note,
            status: ResourceStatus::from(RecordStatus::ACTIVE->value),
            detailInfo: $detailInfo
        );

        $data = $this->repository->insert(entity: $data);

        // Cascade: update EmployeeDebt paid_amount/remaining_amount
        $this->applyTransactionToDebt(
            transaction: $data
        );

        // Rebuild detail_info with updated debt state (after cascade)
        $info = $this->transactionInfoFactory->buildFromIds(
            employeeId: $employeeId,
            employeeDebtId: $command->employeeDebtId,
            payrollDeductionId: $payrollDeductionId,
        );

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: $info->toArray(),
            )->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        $data = $this->repository->update(
            entity: $data
        );

        return EmployeeDebtTransactionResponse::fromEntity(
            entity: $data
        );
    }

    /**
     * Auto-create PayrollDeduction dari EmployeeDebt (reverse flow).
     *
     * Dipanggil oleh create() saat payment_source=payroll dan
     * payroll_deduction_id tidak dikirim. employee_id & deduction_type_id
     * di-derive dari EmployeeDebt. TIDAK melakukan cascade balik ke
     * EmployeeDebtTransaction (karena pemanggil adalah transaction side),
     * sehingga tidak terjadi double-ledger.
     *
     * @return int ID dari PayrollDeduction yang baru dibuat.
     */
    private function createPayrollDeductionForDebt(
        EmployeeDebt $debt,
        int $period,
        int $amount,
        ?string $note = null,
    ): int {
        $employeeId = $debt->getEmployeeId();
        $deductionTypeId = $debt->getDeductionTypeId();

        $this->payrollDeductionDomainService->guardCreate(
            employeeId: $employeeId,
            deductionTypeId: $deductionTypeId,
            period: $period,
            amount: $amount,
        );

        $deductionInfo = $this->payrollDeductionInfoFactory->buildFromIds(
            employeeId: $employeeId,
            deductionTypeId: $deductionTypeId,
            employeeDebtId: $debt->getId(),
        );

        $deductionDetailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: $deductionInfo->toArray()
            )->build();

        $name = DeductionName::generate($employeeId)->toString();

        $note = DeductionNote::fromNullableForDebtCreation(
            note: $note,
            period: $period,
        );

        $deduction = PayrollDeduction::create(
            name: $name,
            employeeId: $employeeId,
            deductionTypeId: $deductionTypeId,
            employeeDebtId: $debt->getId(),
            period: $period,
            amount: $amount,
            note: $note->toString(),
            status: ResourceStatus::from(RecordStatus::ACTIVE->value),
            detailInfo: $deductionDetailInfo
        );

        $deduction = $this->payrollDeductionRepository->insert(
            entity: $deduction
        );

        $deductionId = $deduction->getId();
        if ($deductionId === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => 'PayrollDeduction',
                        'field' => 'id',
                        'value' => 0,
                    ]
                )
            );
        }

        return $deductionId;
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
        int $payrollDeductionId,
        int $payrollPeriod,
        int $amount,
        string $transactionDate,
        ?string $note = null,
    ): EmployeeDebtTransactionResponse {
        $debt = $this->getDebtById(
            employeeDebtId: $employeeDebtId
        );

        $this->domainService->guardDebtApprovedAndActive(
            debt: $debt,
            transactionType: TransactionType::PAYMENT,
        );

        $info = $this->transactionInfoFactory->buildFromIds(
            employeeId: $debt->getEmployeeId(),
            employeeDebtId: $employeeDebtId,
            payrollDeductionId: $payrollDeductionId,
        );

        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: $info->toArray()
            )->build();

        $data = EmployeeDebtTransaction::create(
            name: Name::generate($debt->getEmployeeId())->toString(),
            employeeDebtId: $employeeDebtId,
            employeeId: $debt->getEmployeeId(),
            transactionType: TransactionType::PAYMENT,
            date: $transactionDate,
            amount: $amount,
            paymentSource: PaymentSource::PAYROLL,
            payrollDeductionId: $payrollDeductionId,
            payrollPeriod: $payrollPeriod,
            note: $note,
            status: ResourceStatus::from(RecordStatus::DRAFT->value),
            detailInfo: $detailInfo
        );

        $data = $this->repository->insert(
            entity: $data
        );

        // Cascade: update EmployeeDebt paid_amount/remaining_amount
        $this->applyTransactionToDebt(
            transaction: $data
        );

        // Rebuild detail_info with updated debt state (after cascade)
        $info = $this->transactionInfoFactory->buildFromIds(
            employeeId: $debt->getEmployeeId(),
            employeeDebtId: $employeeDebtId,
            payrollDeductionId: $payrollDeductionId,
        );

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: $info->toArray(),
            )->build();

        $data->updateDetailInfo(detailInfo: $detailInfo);

        $data = $this->repository->update(entity: $data);

        return EmployeeDebtTransactionResponse::fromEntity(
            entity: $data
        );
    }

    /**
     * Apply transaction effect to EmployeeDebt:
     * - payment:        debt.recordPayment(amount)   → increases paid_amount, decreases remaining
     * - adjust_plus:    debt.addDebt(amount)          → increases principal & remaining (nambah hutang)
     * - adjust_minus:   debt.reduceDebt(amount)       → decreases principal & remaining (kurangi hutang)
     */
    private function applyTransactionToDebt(EmployeeDebtTransaction $transaction): void
    {
        $debt = $this->getDebtById(
            employeeDebtId: $transaction->getEmployeeDebtId()
        );
        
        $type = $transaction->getTransactionType();
        $amount = $transaction->getAmount();

        switch ($type) {
            case TransactionType::PAYMENT:
                $debt->recordPayment(amount: $amount);
                break;
            case TransactionType::ADJUST_PLUS:
                $debt->addDebt(amount: $amount);
                break;
            case TransactionType::ADJUST_MINUS:
                $debt->reduceDebt(amount: $amount);
                break;
        }

        $this->employeeDebtRepository->update(
            entity: $debt
        );
    }

    /*
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

    // Reverse transaction effect from EmployeeDebt (sebelum update/delete).

    private function reverseTransactionFromDebt(EmployeeDebtTransaction $transaction): void
    {
        $debt = $this->getDebtById($transaction->getEmployeeDebtId());
        $type = $transaction->getTransactionType();
        $amount = $transaction->getAmount();

        if (TransactionType::isInflow($type)) {
            $debt->reversePayment($amount);
        } elseif (TransactionType::isAdjustPlus($type)) {
            $debt->reduceDebt($amount);
        } elseif (TransactionType::isAdjustMinus($type)) {
            $debt->addDebt($amount);
        }

        $this->employeeDebtRepository->update(entity: $debt);
    }
    */
}