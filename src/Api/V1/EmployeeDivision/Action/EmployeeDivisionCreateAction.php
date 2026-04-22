<?php

declare(strict_types=1);

namespace App\Api\V1\EmployeeDivision\Action;

// Application Layer
use App\Application\EmployeeDivision\EmployeeDivisionApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\EmployeeDivision\Validation\EmployeeDivisionInputValidator;
use App\Application\EmployeeDivision\Command\CreateEmployeeDivisionCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class EmployeeDivisionCreateAction 
{
    private const ALLOWED_KEYS = ['name', 'code', 'status', 'sync_mdb'];

    public function __construct(
        private EmployeeDivisionInputValidator $inputValidator,
        private EmployeeDivisionApplicationService $applicationService,
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

        $command = CreateEmployeeDivisionCommand::create(
            name: (string) $params->get('name'),
            code: (string) $params->get('code'),
            status: $params->get('status'),
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
