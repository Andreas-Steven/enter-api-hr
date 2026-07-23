<?php

declare(strict_types=1);

namespace App\Domain\EmployeeDebt\ValueObject;

use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

/**
 * Awb sebagai Value Object (Immutable)
 */
final readonly class Name
{
    private function __construct(
        private string $value
    ) {}

    public static function generate(int $employeeId): self
    {
        $prefix = 'DEBT';
        $microtime = substr((string) microtime(false), 2, 6);
        $randomNumber = (string) rand(10000, 99999);
        
        $sequence = substr($microtime . $randomNumber, 0, 7);
        $remainder = intval($sequence) % 7;

        return new self(sprintf('%s-%s%d', $prefix, $employeeId, $sequence, $remainder));
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

    public function sync(string $prefix): self
    {
        $newValue = preg_replace('/^.*-/', $prefix . '-', $this->value);
        return new self($newValue);
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