<?php

declare(strict_types=1);

namespace App\Api\V1\Branch\Action;

// Application Layer
use App\Application\Branch\BranchApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\Branch\Validation\BranchInputValidator;
use App\Application\Branch\Command\CreateBranchCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class BranchCreateAction 
{
    private const ALLOWED_KEYS = ['name', 'code', 'address', 'status', 'sync_mdb', 'branch_type'];

    public function __construct(
        private BranchInputValidator $inputValidator,
        private BranchApplicationService $applicationService,
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

        $code = $params->get('code');
        if ($code !== null) {
            $params = $params->with('code', strtoupper((string) $code));
        }

        $this->inputValidator->validate(
            data: $params,
            context: ValidationContext::CREATE,
        );

        $command = CreateBranchCommand::create(
            name: (string) $params->get('name'),
            code: (string) $params->get('code'),
            address: (string) $params->get('address'),
            status: (int) $params->get('status'),
            branchId: (int) $params->get('branch_type'),
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
