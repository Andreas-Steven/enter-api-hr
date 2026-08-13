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
            ruleType: $this->resolveEnum(WorkPatternRuleType::class, $params->get('rule_type')),
            targetCount: $params->get('target_count') === null
                ? null
                : (int) $params->get('target_count'),
            holidayWins: $params->has('holiday_wins') ? (int) $params->get('holiday_wins') : null,
            holidayType: $this->resolveEnum(HolidayType::class, $params->get('holiday_type')),
            substitutionAllowed: $params->has('substitution_allowed') ? (int) $params->get('substitution_allowed') : null,
            excessTreatment: $this->resolveEnum(ExcessTreatment::class, $params->get('excess_treatment')),
            deficitTreatment: $this->resolveEnum(DeficitTreatment::class, $params->get('deficit_treatment')),
            notes: $params->get('notes'),
            status: $params->has('status') ? (int) $params->get('status') : null,
            detailInfo: $params->get('detail_info'),
            lockVersion: $params->has('lock_version') ? (int) $params->get('lock_version') : null,
            targetCountProvided: $params->has('target_count'),
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

    /**
     * Resolve a backed enum value from raw payload, returning null when the
     * field is absent or empty so partial updates don't force-cast "" into
     * an invalid enum backing value.
     *
     * @template T of \BackedEnum
     * @param class-string<T> $enumClass
     * @param mixed $value
     * @return T|null
     */
    private function resolveEnum(string $enumClass, mixed $value): ?object
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $enumClass::from((string) $value);
    }
}
