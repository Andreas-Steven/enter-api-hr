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
use App\Domain\PayrollDeduction\Entity\PayrollDeduction;
use App\Domain\PayrollDeduction\Repository\PayrollDeductionRepositoryInterface;
use App\Domain\PayrollDeduction\Service\PayrollDeductionDomainService;
use App\Domain\PayrollDeduction\ValueObject\Name as DeductionName;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * PayrollDeduction Application Service
 *
 * Orchestrates use cases and coordinates domain & infrastructure.
 *
 * Modul ini standalone untuk tracking potongan gaji. Jika potongan terkait
 * EmployeeDebt (employee_debt_id di-set), akan cascade ke
 * EmployeeDebtTransaction.applyPaymentFromPayrollDeduction untuk record ledger.
 */
final class PayrollDeductionApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private PayrollDeductionDetailInfoFactory $deductionInfoFactory,
        private PayrollDeductionRepositoryInterface $repository,
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
        );

        $info = $this->deductionInfoFactory->buildFromIds(
            employeeId: $command->employeeId,
            deductionTypeId: $command->deductionTypeId,
            employeeDebtId: $command->employeeDebtId,
        );

        $detailInfo = $this->detailInfoFactory
            ->create(detailInfo: $info->toArray())
            ->build();

        $name = DeductionName::generate($command->employeeId, 0)->toString();

        $data = PayrollDeduction::create(
            name: $name,
            employeeId: $command->employeeId,
            deductionTypeId: $command->deductionTypeId,
            employeeDebtId: $command->employeeDebtId,
            period: $command->period,
            amount: $command->amount,
            description: $command->description,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        $deduction = $this->repository->insert(entity: $data);

        // Jika potongan ini terkait EmployeeDebt, record ledger payment
        // supaya debt.paid_amount & remaining_amount ikut ter-update.
        if ($command->employeeDebtId !== null) {
            $this->transactionService->applyPaymentFromPayrollDeduction(
                employeeDebtId: $command->employeeDebtId,
                installmentId: null,
                payrollDeductionId: $deduction->getId(),
                payrollPeriod: $command->period,
                amount: $command->amount,
                transactionDate: date('Y-m-d'),
                note: sprintf('Auto-deducted via payroll %d', $command->period),
            );
        }

        return PayrollDeductionResponse::fromEntity(entity: $deduction);
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

        if ($command->employeeDebtId !== null) {
            $data->setEmployeeDebtId($command->employeeDebtId);
        }

        if ($command->period !== null) {
            $this->domainService->guardPeriod($command->period);
            $data->setPeriod($command->period);
            $data->updateName(
                newName: DeductionName::generate($command->employeeId ?? $data->getEmployeeId(), 0)->toString()
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

        $info = $this->deductionInfoFactory->buildFromIds(
            employeeId: $data->getEmployeeId(),
            deductionTypeId: $data->getDeductionTypeId(),
            employeeDebtId: $data->getEmployeeDebtId(),
        );

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: $info->toArray(),
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
