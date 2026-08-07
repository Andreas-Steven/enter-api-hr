<?php

declare(strict_types=1);

namespace App\Api\V1\EmployeeDebt\Action;

// Application Layer
use App\Application\EmployeeDebt\EmployeeDebtApplicationService;
use App\Application\EmployeeDebt\Command\RejectEmployeeDebtCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\EmployeeDebt\Validation\EmployeeDebtInputValidator;

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
 * EmployeeDebt Reject API Action
 */
final class EmployeeDebtRejectAction
{
    private const ALLOWED_KEYS = ['approved_note', 'lock_version'];

    public function __construct(
        private EmployeeDebtInputValidator $inputValidator,
        private EmployeeDebtApplicationService $applicationService,
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

        $params = $payload?->getRawParams()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS
            )->with('id', $id)
            ->sanitize();

        $this->inputValidator->validate(
            data: $params,
            context: ValidationContext::DEBT_REJECT,
        );

        $command = RejectEmployeeDebtCommand::create(
            id: (int) $id,
            approvedNote: $params->get('approved_note'),
            lockVersion: (int) $params->get('lock_version'),
        );

        $response = $this->applicationService->reject(
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
