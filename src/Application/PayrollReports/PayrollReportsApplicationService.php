<?php

declare(strict_types=1);

namespace App\Application\PayrollReports;

// Application Layer
use App\Application\PayrollReports\Command\CreatePayrollReportsCommand;
use App\Application\PayrollReports\Command\UpdatePayrollReportsCommand;
use App\Application\PayrollReports\Dto\PayrollReportsResponse;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\PayrollReports\Entity\PayrollReports;
use App\Domain\PayrollReports\Repository\PayrollReportsRepositoryInterface;
use App\Domain\PayrollReports\Service\PayrollReportsDomainService;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * PayrollReports Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class PayrollReportsApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private PayrollReportsRepositoryInterface $repository,
        private PayrollReportsDomainService $domainService
    ) {
    }

    public function getResource(): string
    {
        return PayrollReports::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): PayrollReports
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

    public function view(int $id): PayrollReportsResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );
        
        return PayrollReportsResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreatePayrollReportsCommand $command): PayrollReportsResponse
    {
        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: []
            )
            ->build();

        $data = PayrollReports::create(
            name: $command->name,
            attendanceSummaryId: $command->attendanceSummaryId,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        if ($command->payrollSummary !== null) {
            $data->setPayrollSummary($command->payrollSummary);
        }
        if ($command->notes !== null) {
            $data->setNotes($command->notes);
        }

        return PayrollReportsResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
    }

    public function update(int $id, UpdatePayrollReportsCommand $command): PayrollReportsResponse
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

        if ($command->name !== null) {
            $data->updateName(newName: $command->name);
        }
        if ($command->attendanceSummaryId !== null) {
            $data->setAttendanceSummaryId($command->attendanceSummaryId);
        }
        if ($command->payrollSummary !== null) {
            $data->setPayrollSummary($command->payrollSummary);
        }
        if ($command->notes !== null) {
            $data->setNotes($command->notes);
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

        return PayrollReportsResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): PayrollReportsResponse
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
            permission: 'payrollreports.delete',
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

        return PayrollReportsResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): PayrollReportsResponse
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
        
        return PayrollReportsResponse::fromEntity(
            entity: $restoredData
        );
    }
}
