<?php

declare(strict_types=1);

namespace App\Domain\EmployeeDebtTransaction\ValueObject;

use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

/**
 * Name Value Object untuk EmployeeDebtTransaction (Immutable).
 *
 * Format: TXN-{employeeId}{autoGenerate}{remainder}
 */
final readonly class Name
{
    private function __construct(
        private string $value
    ) {}

    public static function generate(int $employeeId): self
    {
        $prefix = 'TXN';
        $microtime = substr((string) microtime(false), 2, 6);
        $randomNumber = (string) rand(10000, 99999);

        $autoGenerate = substr($microtime . $randomNumber, 0, 7);
        $remainder = intval($autoGenerate) % 7;

        return new self(sprintf('%s-%d%d%d', $prefix, $employeeId, $autoGenerate, $remainder));
    }

    public static function fromString(string $value): self
    {
        if (empty($value)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'required',
                    params: ['resource' => 'Name'],
                    domain: 'app',
                )
            );
        }
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
