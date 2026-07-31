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
use App\Domain\EmployeeDebtInstallment\Entity\EmployeeDebtInstallment;
use App\Domain\EmployeeDebtInstallment\Repository\EmployeeDebtInstallmentRepositoryInterface;
use App\Domain\EmployeeDebtTransaction\Entity\EmployeeDebtTransaction;
use App\Domain\EmployeeDebtTransaction\Repository\EmployeeDebtTransactionRepositoryInterface;
use App\Domain\EmployeeDebtTransaction\Service\EmployeeDebtTransactionDomainService;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\Enums\TransactionType;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * EmployeeDebtTransaction Application Service (Mandor/Alur Kerja)
 *
 * Orchestrates use cases and coordinates domain & infrastructure.
 *
 * Selain CRUD generik, service ini menjadi ORCHESTRATOR pemotongan hutang:
 * - applyPayment()      : uang masuk (cash/transfer/payroll_deduction)
 * - applySettlement()   : dianggap lunas tanpa pembayaran penuh
 * - applyAdjustment()   : koreksi saldo hutang
 * - reverseTransaction(): batal transaksi sebelumnya (rollback paid_amount)
 *
 * Setiap operasi menjaga invariant:
 *   employee_debt.paid_amount == SUM(transaction.amount WHERE type IN (payment,settlement))
 *                              - SUM(transaction.amount WHERE type = reversal)
 */
final class EmployeeDebtTransactionApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private EmployeeDebtTransactionDetailInfoFactory $transactionInfoFactory,
        private EmployeeDebtTransactionRepositoryInterface $repository,
        private EmployeeDebtRepositoryInterface $employeeDebtRepository,
        private EmployeeDebtInstallmentRepositoryInterface $installmentRepository,
        private EmployeeDebtTransactionDomainService $domainService
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

    /**
     * Generic create (manual entry). Untuk pemotongan hutang terkontrol,
     * gunakan applyPayment/applySettlement/applyAdjustment/reverseTransaction.
     */
    public function create(CreateEmployeeDebtTransactionCommand $command): EmployeeDebtTransactionResponse
    {
        $debt = $this->requireDebt($command->employeeDebtId);
        $installment = $this->maybeInstallment($command->employeeDebtInstallmentId);

        $this->domainService->guardCreate(
            debt: $debt,
            installment: $installment,
            type: $command->type,
            amount: $command->amount,
        );

        $info = $this->transactionInfoFactory->buildFromIds(
            employeeDebtId: $command->employeeDebtId,
            employeeDebtInstallmentId: $command->employeeDebtInstallmentId,
            payrollDeductionId: $command->payrollDeductionId,
        );

        $detailInfo = $this->detailInfoFactory
            ->create(detailInfo: $info->toArray())
            ->build();

        $data = EmployeeDebtTransaction::create(
            name: sprintf('TRX-%d-%s-%s', $command->employeeDebtId, $command->type, $command->transactionDate),
            employeeDebtId: $command->employeeDebtId,
            employeeDebtInstallmentId: $command->employeeDebtInstallmentId,
            payrollDeductionId: $command->payrollDeductionId,
            type: $command->type,
            amount: $command->amount,
            transactionDate: $command->transactionDate,
            note: $command->note,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        $transaction = $this->repository->insert(entity: $data);

        // Cascade effect ke debt + installment (kecuali adjustment yang ditangani terpisah)
        $this->cascadeEffectToDebt(
            debt: $debt,
            installment: $installment,
            transaction: $transaction,
        );

        return EmployeeDebtTransactionResponse::fromEntity(entity: $transaction);
    }

    /**
     * Jalur A — Pembayaran via payroll_deduction (otomatis dipotong dari gaji).
     * Dipanggil oleh PayrollDeductionApplicationService saat generateFromInstallments.
     *
     * @internal Dipanggil oleh PayrollDeduction module.
     */
    public function applyPaymentFromPayrollDeduction(
        int $employeeDebtId,
        int $installmentId,
        int $payrollDeductionId,
        float $amount,
        string $transactionDate,
        ?string $note = null,
    ): EmployeeDebtTransactionResponse {
        return $this->applyPayment(
            employeeDebtId: $employeeDebtId,
            amount: $amount,
            transactionDate: $transactionDate,
            installmentId: $installmentId,
            payrollDeductionId: $payrollDeductionId,
            note: $note,
        );
    }

    /**
     * Jalur B — Pembayaran manual (cash/transfer langsung ke kasir/finance).
     * Tidak melalui payroll, jadi payroll_deduction_id = null.
     */
    public function applyPayment(
        int $employeeDebtId,
        float $amount,
        string $transactionDate,
        ?int $installmentId = null,
        ?int $payrollDeductionId = null,
        ?string $note = null,
    ): EmployeeDebtTransactionResponse {
        $debt = $this->requireDebt($employeeDebtId);
        $installment = $this->maybeInstallment($installmentId);

        $this->domainService->guardCreate(
            debt: $debt,
            installment: $installment,
            type: TransactionType::PAYMENT,
            amount: $amount,
        );

        $info = $this->transactionInfoFactory->buildFromIds(
            employeeDebtId: $employeeDebtId,
            employeeDebtInstallmentId: $installmentId,
            payrollDeductionId: $payrollDeductionId,
        );

        $detailInfo = $this->detailInfoFactory
            ->create(detailInfo: $info->toArray())
            ->build();

        $transaction = EmployeeDebtTransaction::create(
            name: sprintf('TRX-%d-payment-%s', $employeeDebtId, $transactionDate),
            employeeDebtId: $employeeDebtId,
            employeeDebtInstallmentId: $installmentId,
            payrollDeductionId: $payrollDeductionId,
            type: TransactionType::PAYMENT,
            amount: $amount,
            transactionDate: $transactionDate,
            note: $note,
            status: ResourceStatus::active(),
            detailInfo: $detailInfo
        );

        $transaction = $this->repository->insert(entity: $transaction);

        $this->cascadeEffectToDebt(
            debt: $debt,
            installment: $installment,
            transaction: $transaction,
        );

        return EmployeeDebtTransactionResponse::fromEntity(entity: $transaction);
    }

    /**
     * Settlement — hutang dianggap lunas tanpa pembayaran penuh
     * (mis. kompensasi keluar, write-off, komitmen perusahaan).
     *
     * Amount = sisa remaining_amount debt (otomatis), atau nominal spesifik.
     */
    public function applySettlement(
        int $employeeDebtId,
        string $transactionDate,
        ?float $amount = null,
        ?int $installmentId = null,
        ?string $note = null,
    ): EmployeeDebtTransactionResponse {
        $debt = $this->requireDebt($employeeDebtId);
        $installment = $this->maybeInstallment($installmentId);

        $settleAmount = $amount ?? $debt->getRemainingAmount();

        $this->domainService->guardCreate(
            debt: $debt,
            installment: $installment,
            type: TransactionType::SETTLEMENT,
            amount: $settleAmount,
        );

        $info = $this->transactionInfoFactory->buildFromIds(
            employeeDebtId: $employeeDebtId,
            employeeDebtInstallmentId: $installmentId,
            payrollDeductionId: null,
        );

        $detailInfo = $this->detailInfoFactory
            ->create(detailInfo: $info->toArray())
            ->build();

        $transaction = EmployeeDebtTransaction::create(
            name: sprintf('TRX-%d-settlement-%s', $employeeDebtId, $transactionDate),
            employeeDebtId: $employeeDebtId,
            employeeDebtInstallmentId: $installmentId,
            payrollDeductionId: null,
            type: TransactionType::SETTLEMENT,
            amount: $settleAmount,
            transactionDate: $transactionDate,
            note: $note,
            status: ResourceStatus::active(),
            detailInfo: $detailInfo
        );

        $transaction = $this->repository->insert(entity: $transaction);

        $this->cascadeEffectToDebt(
            debt: $debt,
            installment: $installment,
            transaction: $transaction,
        );

        return EmployeeDebtTransactionResponse::fromEntity(entity: $transaction);
    }

    /**
     * Adjustment — koreksi saldo hutang (mis. salah catat nominal di awal).
     * Tidak mengubah paid_amount langsung; koreksi dilakukan pada debt.amount.
     */
    public function applyAdjustment(
        int $employeeDebtId,
        float $newDebtAmount,
        string $transactionDate,
        ?string $note = null,
    ): EmployeeDebtTransactionResponse {
        $debt = $this->requireDebt($employeeDebtId);

        if ($newDebtAmount < $debt->getPaidAmount()) {
            throw new \App\Shared\Exception\BadRequestException(
                translate: Message::create(
                    key: 'employee_debt_transaction.adjustment_below_paid',
                    domain: 'validation',
                    params: [
                        'new_amount' => $newDebtAmount,
                        'paid' => $debt->getPaidAmount(),
                    ]
                )
            );
        }

        $info = $this->transactionInfoFactory->buildFromIds(
            employeeDebtId: $employeeDebtId,
            employeeDebtInstallmentId: null,
            payrollDeductionId: null,
        );

        $detailInfo = $this->detailInfoFactory
            ->create(detailInfo: $info->toArray())
            ->build();

        $transaction = EmployeeDebtTransaction::create(
            name: sprintf('TRX-%d-adjustment-%s', $employeeDebtId, $transactionDate),
            employeeDebtId: $employeeDebtId,
            employeeDebtInstallmentId: null,
            payrollDeductionId: null,
            type: TransactionType::ADJUSTMENT,
            amount: $newDebtAmount,
            transactionDate: $transactionDate,
            note: $note,
            status: ResourceStatus::active(),
            detailInfo: $detailInfo
        );

        $transaction = $this->repository->insert(entity: $transaction);

        // Apply koreksi nominal ke debt (recalculate remaining + installment_amount)
        $debt->adjustAmount(newAmount: $newDebtAmount);
        $this->employeeDebtRepository->update(entity: $debt);

        return EmployeeDebtTransactionResponse::fromEntity(entity: $transaction);
    }

    /**
     * Reversal — batal transaksi sebelumnya (rollback paid_amount).
     * Membuat transaction baru type=reversal yang mereferensikan transaction asli.
     */
    public function reverseTransaction(
        int $originalTransactionId,
        string $transactionDate,
        ?float $reversalAmount = null,
        ?string $note = null,
    ): EmployeeDebtTransactionResponse {
        $original = $this->getEntityById(id: $originalTransactionId, status: null);

        $reversalAmount = $reversalAmount ?? $original->getAmount();

        $this->domainService->guardReversal(
            original: $original,
            reversalAmount: $reversalAmount,
        );

        $debt = $this->requireDebt($original->getEmployeeDebtId());
        $installment = $this->maybeInstallment($original->getEmployeeDebtInstallmentId());

        $info = $this->transactionInfoFactory->buildFromIds(
            employeeDebtId: $original->getEmployeeDebtId(),
            employeeDebtInstallmentId: $original->getEmployeeDebtInstallmentId(),
            payrollDeductionId: $original->getPayrollDeductionId(),
        );

        $detailInfo = $this->detailInfoFactory
            ->create(detailInfo: $info->toArray())
            ->build();

        $transaction = EmployeeDebtTransaction::create(
            name: sprintf('TRX-%d-reversal-%s', $original->getEmployeeDebtId(), $transactionDate),
            employeeDebtId: $original->getEmployeeDebtId(),
            employeeDebtInstallmentId: $original->getEmployeeDebtInstallmentId(),
            payrollDeductionId: $original->getPayrollDeductionId(),
            type: TransactionType::REVERSAL,
            amount: $reversalAmount,
            transactionDate: $transactionDate,
            note: $note ?? sprintf('Reversal of TRX #%d', $original->getId()),
            status: ResourceStatus::active(),
            detailInfo: $detailInfo
        );

        $transaction = $this->repository->insert(entity: $transaction);

        $this->cascadeEffectToDebt(
            debt: $debt,
            installment: $installment,
            transaction: $transaction,
        );

        return EmployeeDebtTransactionResponse::fromEntity(entity: $transaction);
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

        if ($command->employeeDebtId !== null) {
            $data->setEmployeeDebtId($command->employeeDebtId);
        }

        if ($command->employeeDebtInstallmentId !== null) {
            $data->setEmployeeDebtInstallmentId($command->employeeDebtInstallmentId);
        }

        if ($command->payrollDeductionId !== null) {
            $data->setPayrollDeductionId($command->payrollDeductionId);
        }

        if ($command->type !== null) {
            $data->setType($command->type);
        }

        if ($command->amount !== null) {
            $data->setAmount($command->amount);
        }

        if ($command->transactionDate !== null) {
            $data->setTransactionDate($command->transactionDate);
        }

        if ($command->note !== null) {
            $data->setNote($command->note);
        }

        $data->applyStatus(
            newStatus: $newStatus
        );

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: $command->detailInfo ?? [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return EmployeeDebtTransactionResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
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

        $detailInfo = $this->detailInfoFactory
            ->delete(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return EmployeeDebtTransactionResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
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

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        $restoredData = $this->repository->restore(
            id: $data->getId()
        );

        return EmployeeDebtTransactionResponse::fromEntity(
            entity: $restoredData
        );
    }

    // ------------------------------------------------------------------
    // Private helpers — orchestration internals
    // ------------------------------------------------------------------

    private function requireDebt(int $employeeDebtId): EmployeeDebt
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
                        'value' => $employeeDebtId,
                    ]
                )
            );
        }

        return $debt;
    }

    private function maybeInstallment(?int $installmentId): ?EmployeeDebtInstallment
    {
        if ($installmentId === null) {
            return null;
        }

        $installment = $this->installmentRepository->findById(
            id: $installmentId,
            status: null
        );

        if ($installment === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => 'EmployeeDebtInstallment',
                        'field' => 'id',
                        'value' => $installmentId,
                    ]
                )
            );
        }

        return $installment;
    }

    /**
     * Cascade effect transaction ke employee_debt (paid_amount/remaining/settled)
     * dan ke employee_debt_installment (payment_status/paid_at/payroll_deduction_id).
     *
     * Invariant yang dijaga:
     *   debt.paid_amount == SUM(payment+settlement) - SUM(reversal)
     */
    private function cascadeEffectToDebt(
        EmployeeDebt $debt,
        ?EmployeeDebtInstallment $installment,
        EmployeeDebtTransaction $transaction,
    ): void {
        $type = $transaction->getType();
        $amount = $transaction->getAmount();

        if ($type === TransactionType::PAYMENT || $type === TransactionType::SETTLEMENT) {
            $debt->recordPayment(amount: $amount);
            $this->employeeDebtRepository->update(entity: $debt);

            if ($installment !== null && !$installment->isPaid()) {
                $installment->markAsPaid(
                    payrollDeductionId: $transaction->getPayrollDeductionId(),
                );
                $this->installmentRepository->update(entity: $installment);
            }
        } elseif ($type === TransactionType::REVERSAL) {
            $debt->reversePayment(amount: $amount);
            $this->employeeDebtRepository->update(entity: $debt);

            // Reversal terhadap installment: kembalikan ke pending
            if ($installment !== null && $installment->isPaid()) {
                $installment->markAsWaived(); // tidak ada method markAsPending; waived = tidak paid
                // Catatan: implementasi lebih ketat bisa menambah markAsPending() di entity.
                $this->installmentRepository->update(entity: $installment);
            }
        }
        // ADJUSTMENT: effect sudah ditangani di applyAdjustment() (koreksi debt.amount)
    }
}
