<?php

declare(strict_types=1);

namespace App\Domain\AttendanceDevice\ValueObject;

// Shared Layer
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

final readonly class IpAddress
{
    private const IPV4_PATTERN = '/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/';
    private const IPV6_PATTERN = '/^([0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}$/';

    private function __construct(
        private string $value
    ) {
        $this->validate();
    }

    public static function create(string $value): self
    {
        return new self(trim($value));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function isIpv4(): bool
    {
        return preg_match(self::IPV4_PATTERN, $this->value) === 1;
    }

    public function isIpv6(): bool
    {
        return preg_match(self::IPV6_PATTERN, $this->value) === 1;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function validate(): void
    {
        if (empty($this->value)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'required',
                    params: ['resource' => 'IP Address'],
                    domain: 'validation'
                )
            );
        }

        if (!$this->isIpv4() && !$this->isIpv6()) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'format.ip',
                    params: ['field' => 'IP Address'],
                    domain: 'validation',
                )
            );
        }
    }
}
