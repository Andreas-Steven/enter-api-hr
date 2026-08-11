<?php

declare(strict_types=1);

namespace App\Domain\Employee\ValueObject;

use App\Domain\Employee\Entity\Employee;
use IteratorAggregate;
use Traversable;

/**
 * Immutable collection of Employee entities.
 *
 * Provides an OOP wrapper over array<Employee> with safe construction
 * (null filtering) and iteration support for use in domain services.
 *
 * @implements IteratorAggregate<int, Employee>
 */
final readonly class EmployeeCollection implements IteratorAggregate
{
    /**
     * @param Employee[] $employees
     */
    private function __construct(
        private array $employees,
    ) {}

    /**
     * Creates a collection from a list of Employee entities.
     *
     * @param Employee[] $employees
     */
    public static function fromArray(array $employees): self
    {
        return new self(array_values($employees));
    }

    /**
     * Creates a collection from a list that may contain null entries
     * (e.g. when wrapping a nullable repository lookup result).
     *
     * @param array<?Employee> $employees
     */
    public static function fromNullable(array $employees): self
    {
        return new self(
            array_values(array_filter($employees, fn ($e) => $e !== null))
        );
    }

    /**
     * @return Employee[]
     */
    public function all(): array
    {
        return $this->employees;
    }

    public function isEmpty(): bool
    {
        return $this->employees === [];
    }

    public function count(): int
    {
        return count($this->employees);
    }

    public function getIterator(): Traversable
    {
        yield from $this->employees;
    }
}
