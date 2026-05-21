<?php

declare(strict_types=1);

namespace App\Api\V1\AttendancePolicies\Action;

// Application Layer
use App\Application\AttendancePolicies\AttendancePoliciesApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AttendancePolicies\Validation\AttendancePoliciesInputValidator;
use App\Application\AttendancePolicies\Command\CreateAttendancePoliciesCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AttendancePoliciesCreateAction 
{
    private const ALLOWED_KEYS = ['name', 'policy_detail', 'status', 'sync_mdb'];

    public function __construct(
        private AttendancePoliciesInputValidator $inputValidator,
        private AttendancePoliciesApplicationService $applicationService,
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

        $command = CreateAttendancePoliciesCommand::create(
            name: (string) $params->get('name'),
            policyDetail: $params->get('policy_detail'),
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
}
