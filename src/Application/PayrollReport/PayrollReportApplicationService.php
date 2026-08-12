<?php

declare(strict_types=1);

namespace App\Application\PayrollReport;

// Application Layer
use App\Application\PayrollReport\Command\CreatePayrollReportCommand;
use App\Application\PayrollReport\Command\GeneratePayrollReportCommand;
use App\Application\PayrollReport\Command\UpdatePayrollReportCommand;
use App\Application\PayrollReport\Dto\BatchGenerationResult;
use App\Application\PayrollReport\Dto\PayrollReportResponse;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\PayrollReport\Entity\PayrollReport;
use App\Domain\PayrollReport\Repository\PayrollReportRepositoryInterface;
use App\Domain\PayrollReport\Service\PayrollReportDomainService;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * PayrollReport Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class PayrollReportApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private PayrollReportRepositoryInterface $repository,
        private PayrollReportDomainService $domainService,
    ) {
    }

    public function getResource(): string
    {
        return PayrollReport::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): PayrollReport
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

    public function view(int $id): PayrollReportResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );
        
        return PayrollReportResponse::fromEntity(
            entity: $data
        );
    }

    public function generateAllFromAttendanceSummary(GeneratePayrollReportCommand $command): BatchGenerationResult
    {
        return $this->domainService->generateAllFromAttendanceSummary(command: $command);
    }

    public function create(CreatePayrollReportCommand $command): PayrollReportResponse
    {
        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: []
            )
            ->build();

        $data = PayrollReport::create(
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

        return PayrollReportResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
    }

    public function update(int $id, UpdatePayrollReportCommand $command): PayrollReportResponse
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

        return PayrollReportResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): PayrollReportResponse
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
            permission: 'payroll-report.delete',
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

        return PayrollReportResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): PayrollReportResponse
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
        
        return PayrollReportResponse::fromEntity(
            entity: $restoredData
        );
    }
}
