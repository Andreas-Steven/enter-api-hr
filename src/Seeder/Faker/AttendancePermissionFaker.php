<?php

declare(strict_types=1);

namespace App\Seeder\Faker;

use Faker\Provider\Base;
use Faker\Provider\DateTime;
use Faker\UniqueGenerator;
use App\Infrastructure\Seeder\SeederProviderInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class AttendancePermissionFaker extends Base implements SeederProviderInterface
{
    private array $currentScenario = [];

    public function __construct(\Faker\Generator $generator)
    {
        parent::__construct($generator);
        $generator->addProvider(new DateTime($generator));
    }

    public function employeeFaker(int $id): array
    {
        return [
            'id' => $id,
            'name' => 'Employee ' . $id,
        ];
    }

    public function attendanceFaker(): array
    {
        return [
            'id' => $this->generator->numberBetween(1, 10),
            'date' => $this->generator->dateTimeBetween(date('Y-m-01'), date('Y-m-d'))->format('Y-m-d'),
            'startTime' => $this->generator->dateTimeBetween('07:00:00', '10:00:00')->format('H:i:s'),
            'endTime' => $this->generator->dateTimeBetween('15:00:00', '18:00:00')->format('H:i:s'),
        ];
    }
}