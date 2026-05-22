<?php

declare(strict_types=1);

namespace App\Api\V1\AttendancePolicies\Action;

// Application Layer
use App\Application\AttendancePolicies\AttendancePoliciesApplicationService;
use App\Application\AttendancePolicies\Command\UpdateAttendancePoliciesCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AttendancePolicies\Validation\AttendancePoliciesInputValidator;

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
 * AttendancePolicies Update API Action
 */
final class AttendancePoliciesUpdateAction
{
    private const ALLOWED_KEYS = ['name', 'policy_detail', 'status', 'lock_version'];

    public function __construct(
        private AttendancePoliciesInputValidator $inputValidator,
        private AttendancePoliciesApplicationService $applicationService,
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

        $command = UpdateAttendancePoliciesCommand::create(
            id: (int) $id,
            name: $params->get('name'),
            policyDetail: $params->get('policy_detail'),
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
