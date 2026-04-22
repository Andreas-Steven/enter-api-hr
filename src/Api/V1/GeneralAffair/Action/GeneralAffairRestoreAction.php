<?php

declare(strict_types=1);

namespace App\Api\V1\GeneralAffair\Action;

// Application Layer
use App\Application\GeneralAffair\GeneralAffairApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;

// Shared Layer
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// Vendor Layer
use Yiisoft\Router\CurrentRoute;

/**
 * GeneralAffair Restore Action
 * 
 * Restores a soft-deleted generalaffair back to active status
 */
final readonly class GeneralAffairRestoreAction
{
    public function __construct(
        private GeneralAffairApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        CurrentRoute $currentRoute
    ): ResponseInterface
    {
        $id = $currentRoute->getArgument('id');
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
                httpCode: 400
            );
        }

        $response = $this->applicationService->restore(
            id: (int) $id,
        );

        return $this->responseFactory->success(
            data: $response->toArray(),
            translate: Message::create(
                key: 'resource.restored',
                params: [
                    'resource' => $resource,
                ]
            ),
        );
    }
}
