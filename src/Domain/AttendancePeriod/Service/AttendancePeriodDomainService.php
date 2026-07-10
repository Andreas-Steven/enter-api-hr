<?php

declare(strict_types=1);

namespace App\Domain\AttendancePeriod\Service;

// Domain Layer
use App\Domain\AttendancePeriod\Entity\AttendancePeriod;
use App\Domain\AttendancePeriod\Repository\AttendancePeriodRepositoryInterface;
use App\Domain\AttendancePeriod\ValueObject\AttendancePeriodName;
use App\Domain\AttendancePeriod\ValueObject\EndDate;
use App\Domain\AttendancePeriod\ValueObject\StartDate;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * AttendancePeriod Domain Service
 * 
 * Pure business logic and domain rules
 */
final class AttendancePeriodDomainService
{
    use DomainValidator;
    
    public function __construct(
        private AttendancePeriodRepositoryInterface $repository
    ) {}

    /**
     * Place AttendancePeriod-specific business logic here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     * 
     * AttendancePeriods:
     * - Complex validation rules
     * - Business calculations
     * - Domain-specific operations
     * - Cross-entity business rules
     */

    /**
     * Generate all attendance periods for a given year.
     *
     * @param callable():DetailInfo $detailInfoFactory Factory that returns a fully built DetailInfo instance
     * @return array<int, AttendancePeriod> Array of generated attendance periods
     */
    public function generateYear(int $year, ResourceStatus $status, callable $detailInfoFactory): array
    {
        $generated = [];

        for ($month = 1; $month <= 12; $month++) {
            $periodNumber = (int) sprintf('%d%02d', $year, $month);
            $periodName = AttendancePeriodName::fromYearMonth($year, $month);

            $attendancePeriod = AttendancePeriod::create(
                name: $periodName->toString(),
                period: $periodNumber,
                startDate: StartDate::fromYearMonth($year, $month),
                endDate: EndDate::fromYearMonth($year, $month),
                status: $status,
                detailInfo: $this->resolveDetailInfo($detailInfoFactory)
            );

            $result = $this->repository->upsert($attendancePeriod);
            $generated[] = $result;
        }

        return $generated;
    }

    public function ensurePeriodConsistency(AttendancePeriod $attendancePeriod): void
    {
        $endDate = $attendancePeriod->getEndDate();

        if ($endDate === null) {
            return;
        }

        $period = $attendancePeriod->getPeriod();
        $expectedPeriod = (int) $endDate->toDateTime()->format('Ym');

        if ($period !== $expectedPeriod) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'validation.period.mismatch',
                    domain: 'validation',
                    params: [
                        'resource' => AttendancePeriod::getResource(),
                        'expected' => $expectedPeriod,
                        'actual' => $period,
                    ]
                )
            );
        }
    }

    private function resolveDetailInfo(callable $detailInfoFactory): DetailInfo
    {
        $detailInfo = $detailInfoFactory();

        if (!$detailInfo instanceof DetailInfo) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => AttendancePeriod::getResource(),
                        'field' => 'detail_info',
                        'value' => 'invalid_factory_result'
                    ]
                )
            );
        }

        return $detailInfo;
    }
}