<?php

declare(strict_types=1);

namespace App\Api\V1\GeneralAffair\Action;

// Application Layer
use App\Application\GeneralAffair\GeneralAffairApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\GeneralAffair\Validation\GeneralAffairInputValidator;
use App\Application\GeneralAffair\Command\CreateGeneralAffairCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class GeneralAffairCreateAction 
{
    private const ALLOWED_KEYS = ['product_id', 'category_id', 'location_id', 'asset', 'status', 'sync_mdb'];

    public function __construct(
        private GeneralAffairInputValidator $inputValidator,
        private GeneralAffairApplicationService $applicationService,
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

        $command = CreateGeneralAffairCommand::create(
            /* name: (string) $params->get('name'), */
            productId: (int) $params->get('product_id'),
            categoryId: (int) $params->get('category_id'),
            locationId: (int) $params->get('location_id'),
            asset: $params->get('asset'),
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
