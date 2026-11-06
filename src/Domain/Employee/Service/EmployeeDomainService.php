<?php

declare(strict_types=1);

namespace App\Domain\Employee\Service;

// Domain Layer
use App\Domain\Employee\Entity\Employee;
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;

/**
 * Employee Domain Service
 * 
 * Pure business logic and domain rules
 */
final class EmployeeDomainService
{
    use DomainValidator;

    private const ACTIVATION_REQUIRED_FIELDS = [
        'division_id',
        'position_id',
        'employee_type_id',
        'branch_id',
        'nik',
        'birth_date',
        'date_start',
        'email',
        'contact',
    ];

    private const ACTIVATION_UNASSIGNED_FIELDS = [
        'division_id',
        'position_id',
        'employee_type_id',
        'branch_id',
    ];

    public function __construct(
        private EmployeeRepositoryInterface $repository
    ) {}


    /**
     * Place Employee-specific business logic here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     *
     * Employees:
     * - Complex validation rules
     * - Business calculations
     * - Domain-specific operations
     * - Cross-entity business rules
     */

    /**
     * Validate that employee has all required fields before activation
     */
    public function ensureCanActivate(Employee $employee): void
    {
        $missingFields = $this->getMissingActivationFields($employee);

        if (!empty($missingFields)) {
            $fieldList = implode(', ', $missingFields);

            throw new BadRequestException(
                translate: Message::create(
                    key: 'employee.cannot_activate',
                    params: ['fields' => $fieldList],
                    domain: 'validation'
                )
            );
        }
    }

        /**
     * @return string[]
     */
    private function getMissingActivationFields(Employee $employee): array
    {
        $missingFields = [];

        foreach (self::ACTIVATION_REQUIRED_FIELDS as $field) {
            $value = $this->getActivationFieldValue($employee, $field);

            if ($this->isMissingActivationField($field, $value)) {
                $missingFields[] = $field;
            }
        }

        return $missingFields;
    }

    private function getActivationFieldValue(Employee $employee, string $field): mixed
    {
        return match ($field) {
            'division_id' => $employee->getDivisionId(),
            'position_id' => $employee->getPositionId(),
            'employee_type_id' => $employee->getEmployeeTypeId(),
            'branch_id' => $employee->getBranchId(),
            'nik' => $employee->getNik(),
            'birth_date' => $employee->getBirthDate(),
            'date_start' => $employee->getDateStart(),
            'email' => $employee->getEmail(),
            'contact' => $employee->getContact(),
            default => null,
        };
    }

    private function isMissingActivationField(string $field, mixed $value): bool
    {
        if ($this->isEmptyActivationValue($value)) {
            return true;
        }

        return in_array($field, self::ACTIVATION_UNASSIGNED_FIELDS, true) && $value === 1;
    }

    private function isEmptyActivationValue(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && $value === []);
    }

    /**
     * Validate that contract employee always has contract_end_date
     */
    public function ensureContractEndDate(Employee $employee): void
    {
        if ($employee->isContractType() && $this->isEmptyActivationValue($employee->getContractEndDate())) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'employee.contract_end_date_required',
                    domain: 'app'
                )
            );
        }
    }
}