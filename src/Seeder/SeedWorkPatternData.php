<?php

declare(strict_types=1);

namespace App\Seeder;

use App\Domain\AttendanceShift\Repository\AttendanceShiftRepositoryInterface;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\WorkPattern\Entity\WorkPattern;
use App\Domain\WorkPattern\Repository\WorkPatternRepositoryInterface;
use App\Domain\WorkPattern\ValueObject\WorkingDays;
use App\Domain\WorkPatternRule\Repository\WorkPatternRuleRepositoryInterface;

final class SeedWorkPatternData
{
    public function __construct(
        private WorkPatternRepositoryInterface $workPatternRepository,
        private AttendanceShiftRepositoryInterface $attendanceShiftRepository,
        private WorkPatternRuleRepositoryInterface $workPatternRuleRepository,
    ) {
    }

    public function run(int $count = 10): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $shift = $this->attendanceShiftRepository->findById($i);
            $rule = $this->workPatternRuleRepository->findById($i);

            if ($shift === null || $rule === null) {
                continue;
            }

            $days = [];
            for ($day = 1; $day <= 7; $day++) {
                $days[] = [
                    'day_of_week' => $day,
                    'shift_id' => $day <= 5 ? $shift->getId() : null,
                    'work_pattern_rule_id' => $day <= 5 ? $rule->getId() : null,
                ];
            }

            $workingDays = WorkingDays::fromArray(
                $days,
                $this->attendanceShiftRepository,
                $this->workPatternRuleRepository,
            );

            $entity = WorkPattern::create(
                name: 'Work Pattern ' . $i,
                workingDays: $workingDays,
                status: ResourceStatus::active(),
                detailInfo: DetailInfo::fromArray([]),
            );

            $this->workPatternRepository->insert($entity);
        }

        echo "✅ Seeded " . min($count, $this->getMaxAvailable()) . " WorkPattern records\n";
    }

    private function getMaxAvailable(): int
    {
        $shiftCount = 0;
        $ruleCount = 0;

        for ($i = 1; $i <= 100; $i++) {
            if ($this->attendanceShiftRepository->findById($i) !== null) {
                $shiftCount++;
            }
            if ($this->workPatternRuleRepository->findById($i) !== null) {
                $ruleCount++;
            }
        }

        return min($shiftCount, $ruleCount);
    }
}
