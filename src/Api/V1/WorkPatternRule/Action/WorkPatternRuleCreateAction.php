<?php

declare(strict_types=1);

namespace App\Api\V1\WorkPatternRule\Action;

// Application Layer
use App\Application\WorkPatternRule\WorkPatternRuleApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\WorkPatternRule\Validation\WorkPatternRuleInputValidator;
use App\Application\WorkPatternRule\Command\CreateWorkPatternRuleCommand;

// Domain Layer
use App\Domain\Shared\Enums\DeficitTreatment;
use App\Domain\Shared\Enums\ExcessTreatment;
use App\Domain\Shared\Enums\HolidayType;
use App\Domain\Shared\Enums\WorkPatternRuleType;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class WorkPatternRuleCreateAction
{
    private const ALLOWED_KEYS = [
        'name',
        'rule_type',
        'target_count',
        'holiday_wins',
        'holiday_type',
        'substitution_allowed',
        'excess_treatment',
        'deficit_treatment',
        'notes',
        'status',
        'sync_mdb',
    ];

    public function __construct(
        private WorkPatternRuleInputValidator $inputValidator,
        private WorkPatternRuleApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var \App\Shared\Request\RequestParams $payload */
        $payload = $request->getAttribute('payload');

        $params = $payload->getRawParams()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS
            )->with('status', RecordStatus::DRAFT->value)
            ->sanitize();

        $this->inputValidator->validate(
            data: $params,
            context: ValidationContext::CREATE,
        );

        $command = CreateWorkPatternRuleCommand::create(
            name: (string) $params->get('name'),
            ruleType: WorkPatternRuleType::from((string) $params->get('rule_type')),
            targetCount: $params->get('target_count') === null
                ? null
                : (int) $params->get('target_count'),
            holidayWins: (int) $params->get('holiday_wins'),
            holidayType: $this->resolveHolidayType($params->get('holiday_type')),
            substitutionAllowed: (int) $params->get('substitution_allowed'),
            excessTreatment: ExcessTreatment::from((string) $params->get('excess_treatment')),
            deficitTreatment: DeficitTreatment::from((string) $params->get('deficit_treatment')),
            notes: $params->get('notes'),
            status: (int) $params->get('status'),
            detailInfo: $params->get('detail_info')
        );

        $resource = $this->applicationService->getResource();
        $response = $this->applicationService->create(command: $command);

        return $this->responseFactory->success(
            data: $response->toArray(),
            translate: Message::create(
                key: 'resource.created',
                params: [
                    'resource' => $resource
                ]
            )
        );
    }

    private function resolveHolidayType(mixed $value): ?HolidayType
    {
        if ($value === null || $value === '' || !is_string($value)) {
            return null;
        }

        return HolidayType::from($value);
    }
}
