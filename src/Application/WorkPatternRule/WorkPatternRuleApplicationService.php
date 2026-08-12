<?php

declare(strict_types=1);

namespace App\Application\WorkPatternRule;

// Application Layer
use App\Application\WorkPatternRule\Command\CreateWorkPatternRuleCommand;
use App\Application\WorkPatternRule\Command\UpdateWorkPatternRuleCommand;
use App\Application\WorkPatternRule\Dto\WorkPatternRuleResponse;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\WorkPatternRule\Entity\WorkPatternRule;
use App\Domain\WorkPatternRule\Repository\WorkPatternRuleRepositoryInterface;
use App\Domain\WorkPatternRule\Service\WorkPatternRuleDomainService;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * WorkPatternRule Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class WorkPatternRuleApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private WorkPatternRuleRepositoryInterface $repository,
        private WorkPatternRuleDomainService $domainService
    ) {
    }

    public function getResource(): string
    {
        return WorkPatternRule::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): WorkPatternRule
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

    public function view(int $id): WorkPatternRuleResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );
        
        return WorkPatternRuleResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreateWorkPatternRuleCommand $command): WorkPatternRuleResponse
    {
        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: []
            )
            ->build();

        $data = WorkPatternRule::create(
            name: $command->name,
            ruleType: $command->ruleType,
            targetCount: $command->targetCount,
            holidayWins: $command->holidayWins,
            holidayType: $command->holidayType,
            substitutionAllowed: $command->substitutionAllowed,
            excessTreatment: $command->excessTreatment,
            deficitTreatment: $command->deficitTreatment,
            notes: $command->notes,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        return WorkPatternRuleResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
    }

    public function update(int $id, UpdateWorkPatternRuleCommand $command): WorkPatternRuleResponse
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

        if (!empty($command->ruleType)) {
            $data->updateRuleType($command->ruleType);
        }
        if (!empty($command->targetCount)) {
            $data->updateTargetCount($command->targetCount);
        }
        if (!empty($command->holidayWins)) {
            $data->updateHolidayWins($command->holidayWins);
        }
        if (!empty($command->holidayType)) {
            $data->updateHolidayType($command->holidayType);
        }
        if (!empty($command->substitutionAllowed)) {
            $data->updateSubstitutionAllowed($command->substitutionAllowed);
        }
        if (!empty($command->excessTreatment)) {
            $data->updateExcessTreatment($command->excessTreatment);
        }
        if (!empty($command->deficitTreatment)) {
            $data->updateDeficitTreatment($command->deficitTreatment);
        }
        if (!empty($command->notes)) {
            $data->updateNotes($command->notes);
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

        return WorkPatternRuleResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): WorkPatternRuleResponse
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
            permission: 'work-pattern-rule.delete',
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

        return WorkPatternRuleResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): WorkPatternRuleResponse
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
        
        return WorkPatternRuleResponse::fromEntity(
            entity: $restoredData
        );
    }
}
