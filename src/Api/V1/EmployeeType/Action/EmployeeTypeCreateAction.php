<?php

declare(strict_types=1);

namespace App\Api\V1\EmployeeType\Action;

// Application Layer
use App\Application\EmployeeType\EmployeeTypeApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\EmployeeType\Validation\EmployeeTypeInputValidator;
use App\Application\EmployeeType\Command\CreateEmployeeTypeCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class EmployeeTypeCreateAction 
{
    private const ALLOWED_KEYS = ['name', 'status', 'sync_mdb', 'is_contract'];

    public function __construct(
        private EmployeeTypeInputValidator $inputValidator,
        private EmployeeTypeApplicationService $applicationService,
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

        $command = CreateEmployeeTypeCommand::create(
            name: (string) $params->get('name'),
            status: (int) $params->get('status'),
            isContract: (int) ($params->get('is_contract') ?? 0),
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
