<?php

declare(strict_types=1);

namespace App\Domain\Branch\Service;

// Domain Layer
use App\Domain\Branch\Entity\Branch;
use App\Domain\Branch\Repository\BranchRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;

/**
 * Branch Domain Service
 * 
 * Pure business logic and domain rules
 */
final class BranchDomainService
{
    use DomainValidator;

    private const ACTIVATION_REQUIRED_FIELDS = [
        'attendance_policies_id',
        'work_pattern_id',
    ];

    public function __construct(
        private BranchRepositoryInterface $repository
    ) {}


    /**
     * Place Branch-specific business logic here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     *
     * Branchs:
     * - Complex validation rules
     * - Business calculations
     * - Domain-specific operations
     * - Cross-entity business rules
     */

    /**
     * Validate that Branch has all required fields before activation.
     *
     * The placeholder "Unassign" branch (identified by its code) is exempt
     * from the required-fields check, since it exists only to represent the
     * absence of a real branch assignment.
     */
    public function ensureCanActivate(Branch $Branch): void
    {
        if ($Branch->getCode() === Branch::UNASSIGN_CODE) {
            return;
        }

        $missingFields = $this->getMissingActivationFields($Branch);

        if (!empty($missingFields)) {
            $fieldList = implode(', ', $missingFields);

            throw new BadRequestException(
                translate: Message::create(
                    key: 'branch.cannot_activate',
                    params: ['fields' => $fieldList],
                    domain: 'validation'
                )
            );
        }
    }

        /**
     * @return string[]
     */
    private function getMissingActivationFields(Branch $Branch): array
    {
        $missingFields = [];

        foreach (self::ACTIVATION_REQUIRED_FIELDS as $field) {
            $value = $this->getActivationFieldValue($Branch, $field);

            if ($this->isMissingActivationField($field, $value)) {
                $missingFields[] = $field;
            }
        }

        return $missingFields;
    }

    private function getActivationFieldValue(Branch $Branch, string $field): mixed
    {
        return match ($field) {
            'attendance_policies_id' => $Branch->getAttendancePoliciesId(),
            'work_pattern_id' => $Branch->getWorkPatternId(),
            default => null,
        };
    }

    private function isMissingActivationField(string $field, mixed $value): bool
    {
        return $this->isEmptyActivationValue($value);
    }

    private function isEmptyActivationValue(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && $value === []);
    }
}