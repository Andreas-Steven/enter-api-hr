<?php

declare(strict_types=1);

namespace App\Domain\Employee\ValueObject;

// Shared Layer
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

final readonly class Contact
{
    private array $values;
    private const PHONE_REGEX = '/^\+?[1-9]\d{1,14}$/';

    private function __construct(array $values)
    {
        $this->values = $values;
    }
    
    public static function fromArray(mixed $input): self
    {
        $validatedContacts = [];
        $items = is_array($input) ? $input : [$input];
        
        foreach ($items as $item) {
            $normalized = self::normalize($item);
            if ($normalized === null) {
                continue;
            }

            if (!preg_match(self::PHONE_REGEX, $normalized)) {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'employee.invalid_phone',
                        domain: 'app'
                    )
                );
            }

            $validatedContacts[] = $normalized;
        }

        return new self($validatedContacts);
    }
    
    private static function normalize(mixed $value): ?string
    {
        if (in_array($value, ['[]', '', null, 'null'], true)) {
            return null;
        }

        return trim((string)$value);
    }

    public function toArray(): array
    {
        return $this->values;
    }

    public function hasContacts(): bool
    {
        return !empty($this->values);
    }
}