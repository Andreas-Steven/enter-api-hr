<?php

declare(strict_types=1);

namespace App\Domain\GeneralAffair\Service;

// Domain Layer
use App\Domain\GeneralAffair\Entity\GeneralAffair;
use App\Domain\GeneralAffair\Repository\GeneralAffairRepositoryInterface;
use App\Domain\GeneralAffair\ValueObject\Asset;
use App\Domain\GeneralAffair\ValueObject\AssetItem;
use App\Domain\Shared\Concerns\Entity\ChangeLogged;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Domain Repository
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;
use App\Domain\EmployeeDivision\Repository\EmployeeDivisionRepositoryInterface;

// Shared Layer
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * GeneralAffair Domain Service
 * 
 * Pure business logic and domain rules
 */
final class GeneralAffairDomainService
{
    use ChangeLogged;
    use DomainValidator;
    
    public function __construct(
        private GeneralAffairRepositoryInterface $repository,
        private EmployeeRepositoryInterface $employeeRepository,
        private EmployeeDivisionRepositoryInterface $employeeDivisionRepository,
    ) {}

    /**
     * Place GeneralAffair-specific business logic here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     * 
     * GeneralAffairs:
     * - Complex validation rules
     * - Business calculations
     * - Domain-specific operations
     * - Cross-entity business rules
     */

    /**
     * Builds Asset value object from request payload and resolved referential data.
     */
    public function buildAssetFromPayload(
        array $assetInput,
        array $oldData,
        string $username,
        ?string $now,
    ): Asset {
        if (!isset($assetInput['items']) || !is_array($assetInput['items'])) {
            return Asset::fromItems(
                items: []
            );
        }

        $items = [];
        $processedCodes = [];
        $oldItems = $oldData['items'] ?? [];

        foreach ($assetInput['items'] as $itemPayload) {
            $code = (string) ($itemPayload['code'] ?? '');
            $existingItemData = $oldItems[$code] ?? null;

            $employee = $this->findEmployeeByItemPayload(itemPayload: $itemPayload);
            $division = $this->findDivisionByItemPayload(itemPayload: $itemPayload);

            $items[] = AssetItem::fromPayload(
                data: $itemPayload,
                oldItemData: $existingItemData,
                username: $username,
                now: $now,
                employeeName: $employee->getName(),
                divisionName: $division->getName(),
            );

            $processedCodes[] = $code;
        }

        foreach ($oldItems as $code => $oldItemData) {
            if (!in_array($code, $processedCodes, true)) {
                $items[] = AssetItem::fromStoredArray(data: $oldItemData);
            }
        }

        return Asset::fromItems(items: $items);
    }

    public function buildAssetWithDeletedItemCode(
        array $oldData,
        string $code,
        string $username,
        ?string $now,
    ): Asset {
        $oldItems = $oldData['items'] ?? [];
        $itemData = $oldItems[$code] ?? null;

        if ($itemData === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => 'GeneralAffairAssetItem',
                        'field' => 'code',
                        'value' => $code,
                    ]
                )
            );
        }

        $changeLog = array_merge([
            'created_at' => null,
            'created_by' => null,
            'updated_at' => null,
            'updated_by' => null,
            'deleted_at' => null,
            'deleted_by' => null,
        ], $itemData['items_change_log'] ?? []);

        if (!empty($changeLog['deleted_at'])) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'resource.modification_denied_on_deleted',
                    params: [
                        'resource' => 'GeneralAffairAssetItem',
                        'status' => 'Deleted',
                    ]
                )
            );
        }

        $changeLog['deleted_at'] = $now;
        $changeLog['deleted_by'] = $username;
        $itemData['items_change_log'] = $changeLog;
        $oldItems[$code] = $itemData;

        $items = [];

        foreach ($oldItems as $storedItem) {
            $items[] = AssetItem::fromStoredArray(data: $storedItem);
        }

        return Asset::fromItems(items: $items);
    }

    private function findEmployeeByItemPayload(array $itemPayload): object
    {
        $employeeId = (int) ($itemPayload['employee']['id'] ?? 0);
        $employee = $this->employeeRepository->findById(
            id: $employeeId,
            status: ResourceStatus::active()->value()
        );

        if ($employee === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => 'Employee',
                        'field' => 'id',
                        'value' => $employeeId,
                    ]
                )
            );
        }

        return $employee;
    }

    private function findDivisionByItemPayload(array $itemPayload): object
    {
        $divisionId = (int) ($itemPayload['division']['id'] ?? 0);
        $division = $this->employeeDivisionRepository->findById(        
            id: $divisionId,
            status: ResourceStatus::active()->value()
        );

        if ($division === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => 'EmployeeDivision',
                        'field' => 'id',
                        'value' => $divisionId,
                    ]
                )
            );
        }

        return $division;
    }
}
