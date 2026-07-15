<?php

declare(strict_types=1);

namespace App\Domain\WorkPattern\Service;

// Domain Layer
use App\Domain\WorkPattern\Entity\WorkPattern;
use App\Domain\WorkPattern\Repository\WorkPatternRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

/**
 * WorkPattern Domain Service
 *
 * Pure business logic and domain rules
 */
final class WorkPatternDomainService
{
    use DomainValidator;

    public function __construct(
        private WorkPatternRepositoryInterface $repository
    ) {}

    /**
     * Place WorkPattern-specific business logic here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     *
     * WorkPatterns:
     * - Complex validation rules
     * - Business calculations
     * - Domain-specific operations
     * - Cross-entity business rules
     */

    /**
     * Validate that WorkPattern can be activated.
     *
     * Business invariants for an active work pattern:
     * - Must define at least one working day (a day with a shift assigned).
     * - All referenced shifts must themselves be active.
     *
     * Null shifts are intentionally treated as scheduled days off and are
     * therefore valid; they do NOT block activation.
     */
    public function ensureCanActivate(WorkPattern $workPattern): void
    {
        $workingDays = $workPattern->getWorkingDays();

        if (!$workingDays->hasWorkingDay()) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'work_pattern.cannot_activate.no_working_day',
                    params: ['resource' => $workPattern->getResource()],
                    domain: 'validation'
                )
            );
        }

        $inactiveShiftNames = $workingDays->getInactiveShiftNames();

        if (!empty($inactiveShiftNames)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'work_pattern.cannot_activate.inactive_shifts',
                    params: [
                        'resource' => $workPattern->getResource(),
                        'shifts' => implode(', ', $inactiveShiftNames),
                    ],
                    domain: 'validation'
                )
            );
        }
    }
}
