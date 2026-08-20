<?php

declare(strict_types=1);

namespace App\Application\PayrollRatePolicies;

// Application Layer
use App\Application\PayrollRatePolicies\Command\CreatePayrollRatePoliciesCommand;
use App\Application\PayrollRatePolicies\Command\UpdatePayrollRatePoliciesCommand;
use App\Application\PayrollRatePolicies\Dto\PayrollRatePoliciesResponse;
use App\Application\PayrollRatePolicies\PayrollRatePoliciesDetailInfoFactory;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\PayrollRatePolicies\Entity\PayrollRatePolicies;
use App\Domain\PayrollRatePolicies\Repository\PayrollRatePoliciesRepositoryInterface;
use App\Domain\PayrollRatePolicies\Service\PayrollRatePoliciesDomainService;
use App\Domain\Shared\Enums\PayrollRateType;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * PayrollRatePolicies Application Service (Mandor/Alur Kerja)
 *
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class PayrollRatePoliciesApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private PayrollRatePoliciesDetailInfoFactory $rateDetailInfoFactory,
        private PayrollRatePoliciesRepositoryInterface $repository,
        private PayrollRatePoliciesDomainService $domainService
    ) {
    }

    public function getResource(): string
    {
        return PayrollRatePolicies::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): PayrollRatePolicies
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

    public function view(int $id): PayrollRatePoliciesResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );

        return PayrollRatePoliciesResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreatePayrollRatePoliciesCommand $command): PayrollRatePoliciesResponse
    {
        $rateType = PayrollRateType::from($command->rateType);

        $this->domainService->guardRateConsistency(
            rateType: $rateType,
            deductionTypeId: $command->deductionTypeId,
        );

        $rateInfo = $this->rateDetailInfoFactory->buildFromDeductionTypeId(
            deductionTypeId: $command->deductionTypeId,
        );

        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: $rateInfo->toArray()
            )
            ->build();

        $data = PayrollRatePolicies::create(
            name: $command->name,
            rateType: $rateType,
            deductionTypeId: $command->deductionTypeId,
            amount: $command->amount,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        return PayrollRatePoliciesResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
    }

    public function update(int $id, UpdatePayrollRatePoliciesCommand $command): PayrollRatePoliciesResponse
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

        $data->updateName(
            newName: $command->name
        );

        $newRateType = null;
        if ($command->rateType !== null) {
            $newRateType = PayrollRateType::from($command->rateType);
            $data->updateRateType(
                newRateType: $newRateType
            );
        }

        if ($command->deductionTypeId !== null || ($command->rateType !== null && $newRateType?->isEarning() === true)) {
            // Determine effective incident type for guard.
            $effectiveRateType = $newRateType ?? $data->getRateType();

            // For earning incidents, deduction_type_id should be cleared.
            $effectiveDeductionTypeId = $effectiveRateType->isEarning()
                ? null
                : $command->deductionTypeId;

            $this->domainService->guardRateConsistency(
                rateType: $effectiveRateType,
                deductionTypeId: $effectiveDeductionTypeId,
            );

            $data->updateDeductionTypeId(
                newDeductionTypeId: $effectiveDeductionTypeId
            );
        }

        if ($command->amount !== null) {
            $data->updateAmount(
                newAmount: $command->amount
            );
        }

        $data->applyStatus(
            newStatus: $newStatus
        );

        $detailInfoPayload = $command->detailInfo ?? [];

        if ($command->deductionTypeId !== null || ($newRateType?->isEarning() === true)) {
            $effectiveRateType = $newRateType ?? $data->getRateType();
            $effectiveDeductionTypeId = $effectiveRateType->isEarning()
                ? null
                : $command->deductionTypeId;

            $detailInfoPayload = array_merge(
                $detailInfoPayload,
                $this->rateDetailInfoFactory
                    ->buildFromDeductionTypeId(
                        deductionTypeId: $effectiveDeductionTypeId,
                    )
                    ->toArray(),
            );
        }

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: $detailInfoPayload,
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return PayrollRatePoliciesResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): PayrollRatePoliciesResponse
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
            id: $id,
            authorizer: $this->auth,
            permission: 'payroll-rate-policies.delete',
            resource: $this->getResource(),
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

        return PayrollRatePoliciesResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): PayrollRatePoliciesResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: ResourceStatus::deleted()->value()
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

        return PayrollRatePoliciesResponse::fromEntity(
            entity: $restoredData
        );
    }
}
