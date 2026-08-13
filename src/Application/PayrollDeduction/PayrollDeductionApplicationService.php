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
use App\Domain\PayrollDeduction\ValueObject\Note as DeductionNote;
use App\Domain\Shared\Contract\DateTimeProviderInterface;
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
        private DateTimeProviderInterface $dateTimeProvider,
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
        $context = $this->domainService->resolveDebtContext(
            employeeDebtId: $command->employeeDebtId,
            employeeId: $command->employeeId,
            deductionTypeId: $command->deductionTypeId,
        );

        $this->domainService->guardCreate(
            employeeId: $context->employeeId,
            deductionTypeId: $context->deductionTypeId,
            period: $command->period,
            amount: $command->amount,
        );

        $info = $this->deductionInfoFactory->buildFromIds(
            employeeId: $context->employeeId,
            deductionTypeId: $context->deductionTypeId,
            employeeDebtId: $context->employeeDebtId,
        );

        $detailInfo = $this->detailInfoFactory
            ->create(detailInfo: $info->toArray())
            ->build();

        $data = PayrollDeduction::create(
            name: DeductionName::generate($context->employeeId)->toString(),
            employeeId: $context->employeeId,
            deductionTypeId: $context->deductionTypeId,
            employeeDebtId: $context->employeeDebtId,
            period: $command->period,
            amount: $command->amount,
            note: $command->note,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        $deduction = $this->repository->insert(
            entity: $data
        );

        return PayrollDeductionResponse::fromEntity(
            entity: $deduction
        );
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

        // Pemotongan EmployeeDebt hanya diterapkan saat transisi non-ACTIVE -> ACTIVE.
        $willActivate = $data->isTransitioningToActive($newStatus);

        $hasFieldChanges = $data->hasFieldChanges(
            data: (array) $command,
            removeNulls: true
        );

        $data->guardAgainstInvalidTransition(
            hasFieldChanges: $hasFieldChanges,
            newStatus: $newStatus
        );

        if (!empty($command->employeeId)) {
            $data->setEmployeeId(
                employeeId: $command->employeeId
            );
        }

        if (!empty($command->deductionTypeId)) {
            $data->setDeductionTypeId(
                deductionTypeId: $command->deductionTypeId
            );
        }

        if ($command->employeeDebtId !== null) {
            $data->setEmployeeDebtId(
                employeeDebtId: $command->employeeDebtId
            );
        }

        if ($command->period !== null) {
            $this->domainService->guardPeriod(
                period: $command->period
            );

            $data->setPeriod(
                period: $command->period
            );

            $data->updateName(
                newName: DeductionName::generate(
                    employeeId: $command->employeeId ?? $data->getEmployeeId()
                )->toString()
            );
        }

        if ($command->amount !== null) {
            $data->setAmount(
                amount: $command->amount
            );
        }

        if ($command->note !== null) {
            $data->setNote(
                note: $command->note
            );
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

        $updated = $this->repository->update(
            entity: $data
        );

        /*
         * Jika transisi ke ACTIVE dan potongan terkait EmployeeDebt,
         * terapkan pemotongan hutang (ledger payment) ke EmployeeDebt.
         * Note: jika entity note kosong, auto-generate via Note VO.
         */
        if ($willActivate && $updated->getEmployeeDebtId() !== null) {
            $note = DeductionNote::fromNullable(
                note: $updated->getNote(),
                period: $updated->getPeriod(),
            );

            $this->transactionService->applyPaymentFromPayrollDeduction(
                employeeDebtId: $updated->getEmployeeDebtId(),
                payrollDeductionId: $updated->getId(),
                payrollPeriod: $updated->getPeriod(),
                amount: $updated->getAmount(),
                transactionDate: $this->dateTimeProvider->iso8601(),
                note: $note->toString(),
            );
        }

        return PayrollDeductionResponse::fromEntity(
            entity: $updated
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