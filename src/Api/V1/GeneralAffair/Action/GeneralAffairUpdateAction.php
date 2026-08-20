<?php

declare(strict_types=1);

namespace App\Api\V1\GeneralAffair\Action;

// Application Layer
use App\Application\GeneralAffair\GeneralAffairApplicationService;
use App\Application\GeneralAffair\Command\UpdateGeneralAffairCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\GeneralAffair\Validation\GeneralAffairInputValidator;

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
 * GeneralAffair Update API Action
 */
final class GeneralAffairUpdateAction
{
    private const ALLOWED_KEYS = ['product_id', 'category_id', 'location_id', 'status', 'lock_version'];

    public function __construct(
        private GeneralAffairInputValidator $inputValidator,
        private GeneralAffairApplicationService $applicationService,
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

        $command = UpdateGeneralAffairCommand::create(
            id: (int) $id,
            /* name: $params->get('name'), */
            productId: $params->get('product_id'),
            categoryId: $params->get('category_id'),
            locationId: $params->get('location_id'),
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
