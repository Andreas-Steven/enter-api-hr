<?php

declare(strict_types=1);

namespace App\Api\V1\WorkPatternRule\Action;

// Application Layer
use App\Application\WorkPatternRule\WorkPatternRuleApplicationService;
use App\Application\WorkPatternRule\Command\UpdateWorkPatternRuleCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\WorkPatternRule\Validation\WorkPatternRuleInputValidator;

// Domain Layer
use App\Domain\Shared\Enums\DeficitTreatment;
use App\Domain\Shared\Enums\ExcessTreatment;
use App\Domain\Shared\Enums\HolidayType;
use App\Domain\Shared\Enums\WorkPatternRuleType;

// Shared Layer
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// Vendor Layer
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;

/**
 * WorkPatternRule Update API Action
 */
final class WorkPatternRuleUpdateAction
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
        'lock_version',
    ];

    public function __construct(
        private WorkPatternRuleInputValidator $inputValidator,
        private WorkPatternRuleApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        CurrentRoute $currentRoute,
    ): ResponseInterface
    {
        /** @var \App\Shared\Request\RequestParams|null $payload */
        $id = $currentRoute->getArgument('id');
        $payload = $request->getAttribute('payload');
        $resource = $this->applicationService->getResource();

        if ($id === null) {
            return $this->responseFactory->fail(
                translate: Message::create(
                    key: 'route.parameter_missing',
                    params: [
                        'resource' => $resource,
                        'parameter' => 'id',
                    ]
                ),
                httpCode: Status::NOT_FOUND
            );
        }

        $payload->ensureExists(
            resource: $resource
        );

        $params = $payload->getRawParams()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS
            )->with('id', $id)
            ->sanitize();

        $this->inputValidator->validate(
            data: $params,
            context: ValidationContext::UPDATE,
        );

        $command = UpdateWorkPatternRuleCommand::create(
            id: (int) $id,
            name: $params->get('name'),
            ruleType: WorkPatternRuleType::from((string) $params->get('rule_type')),
            targetCount: (int) $params->get('target_count'),
            holidayWins: (int) $params->get('holiday_wins'),
            holidayType: HolidayType::from((string) $params->get('holiday_type')),
            substitutionAllowed: (int) $params->get('substitution_allowed'),
            excessTreatment: ExcessTreatment::from((string) $params->get('excess_treatment')),
            deficitTreatment: DeficitTreatment::from((string) $params->get('deficit_treatment')),
            notes: $params->get('notes'),
            status: (int) $params->get('status'),
            detailInfo: $params->get('detail_info'),
            lockVersion: (int) $params->get('lock_version'),
        );

        $response = $this->applicationService->update(
            id: (int) $id,
            command: $command
        );

        return $this->responseFactory->success(
            data: $response->toArray(),
            translate: Message::create(
                key: 'resource.updated',
                params: [
                    'resource' => $resource,
                ]
            ),
        );
    }
}
