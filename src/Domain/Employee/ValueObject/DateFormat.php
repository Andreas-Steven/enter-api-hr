<?php

declare(strict_types=1);

namespace App\Domain\Employee\ValueObject;

use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

final readonly class DateFormat
{
    private DateTimeImmutable $dateTime;

    private function __construct(string $dateString)
    {
        $date = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $dateString);
        
        if (!$date) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'date.format',
                    params: [
                        'field' => 'date',
                        'format' => 'ISO8601 (ATOM)',
                    ],
                    domain: 'validation'
                )
            );
        }


        $this->dateTime = $date;
    }

    public static function fromString(string $dateString): self
    {
        return new self($dateString);
    }

    public function toString(): string
    {
        return $this->dateTime->format(DateTimeInterface::ATOM);
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}