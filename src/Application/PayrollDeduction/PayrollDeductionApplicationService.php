<?php

declare(strict_types=1);

namespace App\Application\PayrollDeduction;

// Application Layer
use App\Application\PayrollDeduction\Command\CreatePayrollDeductionCommand;
use App\Application\PayrollDeduction\Command\UpdatePayrollDeductionCommand;
use App\Application\PayrollDeduction\Dto\PayrollDeductionResponse;
use App\Application\PayrollDeduction\PayrollDeductionDetailInfoFactory;
use App\Application\Shared\Factory\DetailInfoFactory;
use App\Application\EmployeeDebtTransaction\EmployeeDebtTransactionApplicationService;

// Domain Layer
use App\Domain\EmployeeDebt\Entity\EmployeeDebt;
use App\Domain\EmployeeDebt\Repository\EmployeeDebtRepositoryInterface;
use App\Domain\EmployeeDebtInstallment\Entity\EmployeeDebtInstallment;
use App\Domain\EmployeeDebtInstallment\Repository\EmployeeDebtInstallmentRepositoryInterface;
use App\Domain\PayrollDeduction\Entity\PayrollDeduction;
use App\Domain\PayrollDeduction\Repository\PayrollDeductionRepositoryInterface;
use App\Domain\PayrollDeduction\Service\PayrollDeductionDomainService;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * PayrollDeduction Application Service (Mandor/Alur Kerja)
 *
 * Orchestrates use cases and coordinates domain & infrastructure.
 *
 * Selain CRUD generik, service ini menjadi ORCHESTRATOR pemotongan gaji
 * otomatis untuk cicilan hutang jatuh tempo:
 * - generateFromInstallments(period): scan installment jatuh tempo di periode
 *   YYYY-MM, buat payroll_deduction per cicilan, lalu cascade ke
 *   EmployeeDebtTransaction.applyPaymentFromPayrollDeduction (record ledger +
 *   update debt.paid_amount + installment.payment_status).
 */
final class PayrollDeductionApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private PayrollDeductionDetailInfoFactory $deductionInfoFactory,
        private PayrollDeductionRepositoryInterface $repository,
        private EmployeeDebtRepositoryInterface $employeeDebtRepository,
        private EmployeeDebtInstallmentRepositoryInterface $installmentRepository,
        private PayrollDeductionDomainService $domainService,
        private EmployeeDebtTransactionApplicationService $transactionService,
    ) {
    }

    public function getResource(): string
    {
        return PayrollDeduction::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): PayrollDeduction
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

    public function view(int $id): PayrollDeductionResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );

        return PayrollDeductionResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreatePayrollDeductionCommand $command): PayrollDeductionResponse
    {
        $this->domainService->guardCreate(
            employeeId: $command->employeeId,
            deductionTypeId: $command->deductionTypeId,
            period: $command->period,
            amount: $command->amount,
            employeeDebtInstallmentId: $command->employeeDebtInstallmentId,
        );

        $info = $this->deductionInfoFactory->buildFromIds(
            employeeId: $command->employeeId,
            deductionTypeId: $command->deductionTypeId,
            employeeDebtId: $command->employeeDebtId,
            employeeDebtInstallmentId: $command->employeeDebtInstallmentId,
        );

        $detailInfo = $this->detailInfoFactory
            ->create(detailInfo: $info->toArray())
            ->build();

        $name = sprintf('DED-%d-%s', $command->employeeId, $command->period);

        $data = PayrollDeduction::create(
            name: $name,
            employeeId: $command->employeeId,
            deductionTypeId: $command->deductionTypeId,
            attendanceReportId: $command->attendanceReportId,
            employeeDebtId: $command->employeeDebtId,
            employeeDebtInstallmentId: $command->employeeDebtInstallmentId,
            period: $command->period,
            amount: $command->amount,
            description: $command->description,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        $deduction = $this->repository->insert(entity: $data);

        // Jika potongan ini terkait installment hutang, record ledger payment
        // supaya debt.paid_amount & installment.payment_status ikut ter-update.
        if ($command->employeeDebtId !== null && $command->employeeDebtInstallmentId !== null) {
            $this->transactionService->applyPaymentFromPayrollDeduction(
                employeeDebtId: $command->employeeDebtId,
                installmentId: $command->employeeDebtInstallmentId,
                payrollDeductionId: $deduction->getId(),
                amount: $command->amount,
                transactionDate: $command->period . '-01',
                note: sprintf('Auto-deducted via payroll %s', $command->period),
            );
        }

        return PayrollDeductionResponse::fromEntity(entity: $deduction);
    }

    /**
     * Generate payroll_deduction otomatis untuk SEMUA cicilan jatuh tempo
     * pada periode YYYY-MM yang belum dibayar & belum pernah di-generate.
     *
     * Alur:
     * 1. Ambil installment jatuh tempo periode ini (findDueForPeriod)
     * 2. Untuk tiap installment:
     *    a. Validasi eligible (pending + belum di-generate)
     *    b. Ambil employee_debt terkait untuk dapat employee_id & deduction_type_id
     *    c. Buat payroll_deduction (amount = installment.amount)
     *    d. Cascade ke EmployeeDebtTransaction.applyPaymentFromPayrollDeduction
     *
     * @param string $period Format YYYY-MM
     * @return list<PayrollDeductionResponse>
     */
    public function generateFromInstallments(string $period): array
    {
        $this->domainService->guardPeriod($period);

        $installments = $this->installmentRepository->findDueForPeriod(
            period: $period,
            status: null
        );

        $results = [];

        foreach ($installments as $installment) {
            // Skip jika sudah pernah di-generate (race-safe double check)
            if ($this->repository->existsForInstallment($installment->getId())) {
                continue;
            }

            $this->domainService->guardInstallmentEligibleForGeneration($installment);

            $debt = $this->employeeDebtRepository->findById(
                id: $installment->getEmployeeDebtId(),
                status: null
            );

            if ($debt === null) {
                // Orphan installment (debt sudah dihapus) — skip, jangan fail batch
                continue;
            }

            $info = $this->deductionInfoFactory->buildFromIds(
                employeeId: $debt->getEmployeeId(),
                deductionTypeId: $debt->getDeductionTypeId(),
                employeeDebtId: $debt->getId(),
                employeeDebtInstallmentId: $installment->getId(),
            );

            $detailInfo = $this->detailInfoFactory
                ->create(detailInfo: $info->toArray())
                ->build();

            $name = sprintf('DED-%d-%s-INS%d', $debt->getEmployeeId(), $period, $installment->getId());

            $deduction = PayrollDeduction::create(
                name: $name,
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
                    $installment->getDueDate()
                ),
                status: ResourceStatus::active(),
                detailInfo: $detailInfo
            );

            $deduction = $this->repository->insert(entity: $deduction);

            // Cascade: record ledger payment + update debt + mark installment paid
            $this->transactionService->applyPaymentFromPayrollDeduction(
                employeeDebtId: $debt->getId(),
                installmentId: $installment->getId(),
                payrollDeductionId: $deduction->getId(),
                amount: $installment->getAmount(),
                transactionDate: $period . '-01',
                note: sprintf('Auto-deducted via payroll %s (installment #%d)', $period, $installment->getSequence()),
            );

            $results[] = PayrollDeductionResponse::fromEntity(entity: $deduction);
        }

        return $results;
    }

    public function update(int $id, UpdatePayrollDeductionCommand $command): PayrollDeductionResponse
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

        if (!empty($command->employeeId)) {
            $data->setEmployeeId($command->employeeId);
        }

        if (!empty($command->deductionTypeId)) {
            $data->setDeductionTypeId($command->deductionTypeId);
        }

        if ($command->attendanceReportId !== null) {
            $data->setAttendanceReportId($command->attendanceReportId);
        }

        if ($command->employeeDebtId !== null) {
            $data->setEmployeeDebtId($command->employeeDebtId);
        }

        if ($command->employeeDebtInstallmentId !== null) {
            $data->setEmployeeDebtInstallmentId($command->employeeDebtInstallmentId);
        }

        if (!empty($command->period)) {
            $this->domainService->guardPeriod($command->period);
            $data->setPeriod($command->period);
            $data->updateName(
                newName: sprintf('DED-%d-%s', $command->employeeId ?? $data->getEmployeeId(), $command->period)
            );
        }

        if ($command->amount !== null) {
            $data->setAmount($command->amount);
        }

        if ($command->description !== null) {
            $data->setDescription($command->description);
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

        return PayrollDeductionResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): PayrollDeductionResponse
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
            permission: 'payroll-deduction.delete',
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

        return PayrollDeductionResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): PayrollDeductionResponse
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

        return PayrollDeductionResponse::fromEntity(
            entity: $restoredData
        );
    }
}
