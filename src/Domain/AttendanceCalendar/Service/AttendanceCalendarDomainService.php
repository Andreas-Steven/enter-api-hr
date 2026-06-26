<?php

declare(strict_types=1);

namespace App\Domain\AttendanceCalendar\Service;

// Domain Layer
use App\Domain\AttendanceCalendar\Entity\AttendanceCalendar;
use App\Domain\AttendanceCalendar\Repository\AttendanceCalendarRepositoryInterface;
use App\Domain\AttendanceCalendar\ValueObject\Scope;
use App\Domain\Branch\Entity\Branch;
use App\Domain\Branch\Repository\BranchRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * AttendanceCalendar Domain Service
 * 
 * Pure business logic and domain rules
 */
final class AttendanceCalendarDomainService
{
    use DomainValidator;
    
    public function __construct(
        private AttendanceCalendarRepositoryInterface $repository,
        private BranchRepositoryInterface $branchRepository
    ) {}

    /**
     * Place AttendanceCalendar-specific business logic here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     *
     * AttendanceCalendars:
     * - Complex validation rules
     * - Business calculations
     * - Domain-specific operations
     * - Cross-entity business rules
     */

    /**
     * @param array<int, array{branch_id: int}|array{id: int, code: string, name: string}> $scopePayload
     * @return array<int, array{id: int, code: string, name: string}>
     */
    public function buildScopeFromPayload(array $scopePayload): array
    {
        if (empty($scopePayload)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'scope.payload_empty',
                    domain: 'validation'
                )
            );
        }

        $firstItem = $scopePayload[0] ?? [];

        if (isset($firstItem['id'], $firstItem['code'], $firstItem['name'])) {
            Scope::validateBranchesExist(
                branches: $scopePayload,
                existsById: fn(int $branchId) => $this->branchRepository->existsById($branchId)
            );

            return $scopePayload;
        }

        $branchIds = array_values(array_filter(array_unique(array_map(
            static fn(array $item) => (int) ($item['branch_id'] ?? 0),
            $scopePayload
        )), static fn(int $id) => $id > 0));

        if (empty($branchIds)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'scope.branch_ids_empty',
                    domain: 'validation'
                )
            );
        }

        /** @var array<int, Branch> $branches */
        $branches = $this->branchRepository->findByIds($branchIds);
        $branchesById = [];

        foreach ($branches as $branch) {
            $branchesById[$branch->getId()] = [
                'id' => $branch->getId(),
                'code' => $branch->getCode(),
                'name' => $branch->getName(),
            ];
        }

        $scopeData = [];
        $missingIds = [];

        foreach ($branchIds as $branchId) {
            if (!isset($branchesById[$branchId])) {
                $missingIds[] = $branchId;
                continue;
            }

            $scopeData[] = $branchesById[$branchId];
        }

        if (!empty($missingIds)) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'branch.not_found',
                    params: [
                        'ids' => implode(', ', $missingIds),
                    ]
                )
            );
        }

        return $scopeData;
    }

    /**
     * @param array<int, array{id: int, code: string, name: string}> $scopeData
     */
    public function ensureNoBranchConflict(string $date, array $scopeData, ?int $excludeId = null): void
    {
        $branchIds = array_values(array_filter(array_map(
            static fn(array $branch) => (int) ($branch['id'] ?? 0),
            $scopeData
        ), static fn(int $id) => $id > 0));

        if (empty($branchIds)) {
            return;
        }

        $conflictingIds = $this->repository->findConflictingBranchIds(
            date: $date,
            branchIds: $branchIds,
            excludeId: $excludeId,
        );

        if (empty($conflictingIds)) {
            return;
        }

        $primaryBranch = collect($scopeData)
            ->first(static fn(array $branch) => in_array((int) ($branch['id'] ?? 0), $conflictingIds, true))['name']
            ?? 'Unknown Branch';

        throw new BadRequestException(
            translate: Message::create(
                key: 'attendance_calendar.branch_conflict',
                domain: 'validation',
                params: [
                    'date' => $date,
                    'branch' => $primaryBranch,
                ]
            )
        );
    }
}
