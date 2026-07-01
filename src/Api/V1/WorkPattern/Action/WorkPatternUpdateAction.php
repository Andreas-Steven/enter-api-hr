<?php

declare(strict_types=1);

namespace App\Api\V1\WorkPattern\Action;

// Application Layer
use App\Application\WorkPattern\WorkPatternApplicationService;
use App\Application\WorkPattern\Command\UpdateWorkPatternCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\WorkPattern\Validation\WorkPatternInputValidator;

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
 * WorkPattern Update API Action
 */
final class WorkPatternUpdateAction
{
    private const ALLOWED_KEYS = ['name', 'working_days', 'status', 'lock_version'];

    public function __construct(
        private WorkPatternInputValidator $inputValidator,
        private WorkPatternApplicationService $applicationService,
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
            resource: $resource,
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

        // Ensure working_days is null if not provided or empty
        $workingDays = $params->get('working_days');
        if ($workingDays === null || (is_array($workingDays) && count($workingDays) === 0)) {
            $workingDays = null;
        }

        $command = UpdateWorkPatternCommand::create(
            id: (int) $id,
            name: $params->get('name'),
            workingDays: $workingDays,
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
