<?php

declare(strict_types=1);

namespace App\Api\V1\PayrollDeductionType\Action;

// Application Layer
use App\Application\PayrollDeductionType\PayrollDeductionTypeApplicationService;
use App\Application\PayrollDeductionType\Command\UpdatePayrollDeductionTypeCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\PayrollDeductionType\Validation\PayrollDeductionTypeInputValidator;

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
 * PayrollDeductionType Update API Action
 */
final class PayrollDeductionTypeUpdateAction
{
    private const ALLOWED_KEYS = [
        'name',
        'category',
        'creates_debt',
        'status',
        'detail_info',
        'lock_version',
    ];

    public function __construct(
        private PayrollDeductionTypeInputValidator $inputValidator,
        private PayrollDeductionTypeApplicationService $applicationService,
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

        $command = UpdatePayrollDeductionTypeCommand::create(
            id: (int) $id,
            name: $params->get('name'),
            category: $params->get('category'),
            createsDebt: $params->has('creates_debt')
                ? (int) $params->get('creates_debt')
                : null,
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
