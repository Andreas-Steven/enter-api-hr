<?php

declare(strict_types=1);

namespace App\Api\V1\DeductionType\Action;

// Application Layer
use App\Application\DeductionType\DeductionTypeApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\DeductionType\Validation\DeductionTypeInputValidator;
use App\Application\DeductionType\Command\CreateDeductionTypeCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DeductionTypeCreateAction 
{
    private const ALLOWED_KEYS = ['name', 'category', 'creates_debt','status', 'sync_mdb'];

    public function __construct(
        private DeductionTypeInputValidator $inputValidator,
        private DeductionTypeApplicationService $applicationService,
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

        $command = CreateDeductionTypeCommand::create(
            name: (string) $params->get('name'),
            category: (string) $params->get('category'),
            createsDebt: (int) $params->get('creates_debt'),
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
