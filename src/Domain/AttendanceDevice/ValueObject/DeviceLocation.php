<?php

declare(strict_types=1);

namespace App\Domain\AttendanceDevice\ValueObject;

// Shared Layer
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

final readonly class DeviceLocation
{
    private function __construct(
        private float $latitude,
        private float $longitude
    ) {
        $this->validate();
    }

    public static function create(float $latitude, float $longitude): self
    {
        return new self($latitude, $longitude);
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['lat'], $data['lng'])) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'validation.invalid_format',
                    domain: 'validation',
                    params: ['required_keys' => 'lat, lng']
                )
            );
        }

        return new self(
            latitude: (float) $data['lat'],
            longitude: (float) $data['lng']
        );
    }

    public static function fromString(string $jsonString): self
    {
        $data = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'format.json',
                    params: ['field' => 'Device Location'],
                    domain: 'error',
                )
            );
        }
        
        if (!is_array($data)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'array.must_contain',
                    params: [
                        'resource' => 'DeviceLocation', 
                        'field' => 'lat, lng'
                    ],
                    domain: 'app',
                )
            );
        }
        
        return self::fromArray($data);
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function toArray(): array
    {
        return [
            'lat' => $this->latitude,
            'lng' => $this->longitude,
        ];
    }

    public function equals(self $other): bool
    {
        return 
            abs($this->latitude - $other->latitude) < 0.000001 &&
            abs($this->longitude - $other->longitude) < 0.000001;
    }

    public function distanceTo(self $other): float
    {
        // Haversine formula for calculating distance between two points
        $earthRadius = 6371; // Earth's radius in kilometers

        $latDiff = deg2rad($other->latitude - $this->latitude);
        $lonDiff = deg2rad($other->longitude - $this->longitude);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($other->latitude)) *
             sin($lonDiff / 2) * sin($lonDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c; // Distance in kilometers
    }

    private function validate(): void
    {
        if ($this->latitude < -90 || $this->latitude > 90) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'invalid.latitude',
                    domain: 'app',
                )
            );
        }

        if ($this->longitude < -180 || $this->longitude > 180) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'invalid.longitude',
                    domain: 'app',
                )
            );
        }

        if ($this->latitude === 0.0 && $this->longitude === 0.0) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'invalid.location_zero',
                    domain: 'app',
                )
            );
        }
    }

    public function __toString(): string
    {
        return json_encode($this->toArray());
    }
}
