<?php

declare(strict_types=1);

namespace App\Domain\WorkPatternRule\Entity;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;

// Domain Layer
use App\Domain\Shared\Concerns\Entity\Stateful;
use App\Domain\Shared\Concerns\Entity\Identifiable;
use App\Domain\Shared\Concerns\Entity\Descriptive;

use App\Domain\Shared\Enums\DeficitTreatment;
use App\Domain\Shared\Enums\ExcessTreatment;
use App\Domain\Shared\Enums\HolidayType;
use App\Domain\Shared\Enums\WorkPatternRuleType;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;

final class WorkPatternRule
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'WorkPatternRule';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private WorkPatternRuleType $ruleType,
        private ?int $targetCount,
        private int $holidayWins,
        /** @var list<HolidayType>|null */
        private ?array $holidayTypes,
        private int $substitutionAllowed,
        private ExcessTreatment $excessTreatment,
        private DeficitTreatment $deficitTreatment,
        private ?string $notes,
        private ResourceStatus $status,
        private DetailInfo $detailInfo,
        private ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ) {
        self::guardTargetCount(
            ruleType: $ruleType,
            targetCount: $targetCount,
        );

        $this->resource = self::RESOURCE;
        $this->lockVersion = $lockVersion ?? LockVersion::create();
    }

    public static function getResource(): string
    {
        return self::RESOURCE;
    }

    public static function create(
        string $name,
        WorkPatternRuleType $ruleType,
        ?int $targetCount,
        int $holidayWins,
        /** @var list<HolidayType>|null $holidayTypes */
        ?array $holidayTypes,
        int $substitutionAllowed,
        ExcessTreatment $excessTreatment,
        DeficitTreatment $deficitTreatment,
        ?string $notes,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
    ): self {
        self::guardInitialStatus(
            status: $status,
            resource: self::RESOURCE
        );

        return new self(
            id: null,
            name: $name,
            ruleType: $ruleType,
            targetCount: $targetCount,
            holidayWins: $holidayWins,
            holidayTypes: $holidayTypes,
            substitutionAllowed: $substitutionAllowed,
            excessTreatment: $excessTreatment,
            deficitTreatment: $deficitTreatment,
            notes: $notes,
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            lockVersion: LockVersion::create()
        );
    }

    public static function reconstitute(
        int $id,
        string $name,
        WorkPatternRuleType $ruleType,
        ?int $targetCount,
        int $holidayWins,
        /** @var list<HolidayType>|null $holidayTypes */
        ?array $holidayTypes,
        int $substitutionAllowed,
        ExcessTreatment $excessTreatment,
        DeficitTreatment $deficitTreatment,
        ?string $notes,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ): self {

        return new self(
            id: $id,
            name: $name,
            ruleType: $ruleType,
            targetCount: $targetCount,
            holidayWins: $holidayWins,
            holidayTypes: $holidayTypes,
            substitutionAllowed: $substitutionAllowed,
            excessTreatment: $excessTreatment,
            deficitTreatment: $deficitTreatment,
            notes: $notes,
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            lockVersion: $lockVersion ?? LockVersion::create()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'rule_type' => $this->ruleType->value,
            'target_count' => $this->targetCount,
            'holiday_wins' => $this->holidayWins,
            'holiday_type' => $this->getHolidayTypeValues(),
            'substitution_allowed' => $this->substitutionAllowed,
            'excess_treatment' => $this->excessTreatment->value,
            'deficit_treatment' => $this->deficitTreatment->value,
            'notes' => $this->notes,
            'status' => $this->status->value(),
            'detail_info' => $this->detailInfo->toArray(),
            SyncMdb::field() => $this->syncMdb?->value(),
            LockVersion::field() => $this->lockVersion->value(),
        ];
    }

    /**
     * Compact representation for embedding inside parent responses
     * (e.g. inside WorkPattern's working_days). Excludes administrative
     * fields like notes, status, detail_info, sync_mdb, lock_version.
     */
    public function toSummaryArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'rule_type' => $this->ruleType->value,
            'target_count' => $this->targetCount,
            'holiday_wins' => $this->holidayWins,
            'holiday_type' => $this->getHolidayTypeValues(),
            'substitution_allowed' => $this->substitutionAllowed,
            'excess_treatment' => $this->excessTreatment->value,
            'deficit_treatment' => $this->deficitTreatment->value,
        ];
    }

    public function getRuleType(): WorkPatternRuleType
    {
        return $this->ruleType;
    }

    public function getTargetCount(): ?int
    {
        return $this->targetCount;
    }

    public function getHolidayWins(): int
    {
        return $this->holidayWins;
    }

    /**
     * @return list<HolidayType>|null
     */
    public function getHolidayTypes(): ?array
    {
        return $this->holidayTypes;
    }

    public function getSubstitutionAllowed(): int
    {
        return $this->substitutionAllowed;
    }

    public function getExcessTreatment(): ExcessTreatment
    {
        return $this->excessTreatment;
    }

    public function getDeficitTreatment(): DeficitTreatment
    {
        return $this->deficitTreatment;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function updateRuleType(?WorkPatternRuleType $ruleType): void
    {
        if ($ruleType === null) {
            return;
        }

        self::guardTargetCount(
            ruleType: $ruleType,
            targetCount: $this->targetCount,
        );

        $this->ruleType = $ruleType;
    }

    public function updateTargetCount(?int $targetCount): void
    {
        self::guardTargetCount(
            ruleType: $this->ruleType,
            targetCount: $targetCount,
        );

        $this->targetCount = $targetCount;
    }

    public function updateHolidayWins(?int $holidayWins): void
    {
        if ($holidayWins === null) {
            return;
        }
        $this->holidayWins = $holidayWins;
    }

    /**
     * @param list<HolidayType>|null $holidayTypes
     */
    public function updateHolidayTypes(?array $holidayTypes): void
    {
        // Allow explicitly clearing holiday_type by passing null
        $this->holidayTypes = $holidayTypes;
    }

    public function updateSubstitutionAllowed(?int $substitutionAllowed): void
    {
        if ($substitutionAllowed === null) {
            return;
        }
        $this->substitutionAllowed = $substitutionAllowed;
    }

    public function updateExcessTreatment(?ExcessTreatment $excessTreatment): void
    {
        if ($excessTreatment === null) {
            return;
        }
        $this->excessTreatment = $excessTreatment;
    }

    public function updateDeficitTreatment(?DeficitTreatment $deficitTreatment): void
    {
        if ($deficitTreatment === null) {
            return;
        }
        $this->deficitTreatment = $deficitTreatment;
    }

    public function updateNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    /**
     * @return list<string>|null
     */
    public function getHolidayTypeValues(): ?array
    {
        if ($this->holidayTypes === null) {
            return null;
        }

        return array_values(array_map(
            static fn(HolidayType $holidayType): string => $holidayType->value,
            $this->holidayTypes,
        ));
    }

    private static function guardTargetCount(
        WorkPatternRuleType $ruleType,
        ?int $targetCount,
    ): void {
        if ($targetCount !== null || $ruleType === WorkPatternRuleType::REQUIRED_WEEKDAY) {
            return;
        }

        throw new BadRequestException(
            translate: Message::create(
                key: 'required',
                domain: 'validation',
                params: ['resource' => 'target_count'],
            ),
        );
    }

    public function restore(): void
    {
        if (!$this->status->isDeleted()) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'resource.not_deleted',
                    params: ['id' => $this->id]
                )
            );
        }

        $this->status = ResourceStatus::restored();
    }

    /**
     * Place WorkPatternRule-specific business functions here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     */

}
