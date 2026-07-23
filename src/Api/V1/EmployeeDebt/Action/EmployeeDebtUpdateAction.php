<?php

declare(strict_types=1);

namespace App\Api\V1\EmployeeDebt\Action;

// Application Layer
use App\Application\EmployeeDebt\EmployeeDebtApplicationService;
use App\Application\EmployeeDebt\Command\UpdateEmployeeDebtCommand;

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
 * EmployeeDebt Update API Action
 */
final class EmployeeDebtUpdateAction
{
    private const ALLOWED_KEYS = ['employee_id', 'deduction_type_id', 'amount', 'date', 'note', 'status', 'lock_version'];

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

        $command = UpdateEmployeeDebtCommand::create(
            id: (int) $id,
            employeeId: $params->get('employee_id'),
            deductionTypeId: $params->get('deduction_type_id'),
            amount: (float) $params->get('amount'),
            date: $params->get('date'),
            note: $params->get('note'),
            status: (int) $params->get('status'),
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
