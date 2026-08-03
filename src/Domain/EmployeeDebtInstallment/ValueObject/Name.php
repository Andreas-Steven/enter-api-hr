<?php

declare(strict_types=1);

namespace App\Domain\EmployeeDebtInstallment\ValueObject;

use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

final readonly class Name
{
    private function __construct(
        private string $value
    ) {}

    /**
     * Generate: INS-{employeeDebtId}{autoGenerate}{month}
     * autoGenerate = microtime + random, no prefix, month appended without divider.
     */
    public static function generate(int $employeeDebtId): self
    {
        $prefix = 'INS';
        $microtime = substr((string) microtime(false), 2, 6);
        $randomNumber = (string) rand(10000, 99999);

        $sequence = substr($microtime . $randomNumber, 0, 7);
        $remainder = intval($sequence) % 7;
        $month = (string) date('n');

        return new self(sprintf('%s-%d%d%d%s', $prefix, $employeeDebtId, $sequence, $remainder, $month));
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
