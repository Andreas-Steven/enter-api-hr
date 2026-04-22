<?php

declare(strict_types=1);

namespace App\Domain\Employee\ValueObject;

// Shared Layer
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

final readonly class Image
{
    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromArray(mixed $input): self
    {
        $validValue = null;
        $items = is_array($input) ? $input : [$input];

        foreach ($items as $item) {
            $normalized = self::normalize($item);
            if ($normalized !== null) {
                $validValue = $normalized;
                break;
            }
        }

        if ($validValue === null) {
            return new self('');
        }

        if (!filter_var($validValue, FILTER_VALIDATE_URL)) {
            throw new BadRequestException(
                    translate: Message::create(
                        key: 'format.url',
                        params: [
                            'field' => 'Image'
                        ],
                        domain: 'validation'
                    )
                );
        }

        return new self($validValue);
    }

    private static function normalize(mixed $value): ?string
    {
        if (in_array($value, ['[]', '', 'null', null], true)) {
            return null;
        }

        return (string)$value;
    }

    public function toArray(): array
    {
        return $this->value !== '' ? [$this->value] : [];
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function isEmpty(): bool
    {
        return $this->value === '';
    }
}