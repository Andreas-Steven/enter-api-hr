<?php

declare(strict_types=1);

namespace App\Api\V1\PayrollDeduction\Action;

// Application Layer
use App\Application\PayrollDeduction\PayrollDeductionApplicationService;
use App\Application\PayrollDeduction\Command\UpdatePayrollDeductionCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\PayrollDeduction\Validation\PayrollDeductionInputValidator;

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
 * PayrollDeduction Update API Action
 */
final class PayrollDeductionUpdateAction
{
    private const ALLOWED_KEYS = [
        'employee_id',
        'deduction_type_id',
        'employee_debt_id',
        'period',
        'amount',
        'description',
        'status',
        'detail_info',
        'lock_version',
    ];

    public function __construct(
        private PayrollDeductionInputValidator $inputValidator,
        private PayrollDeductionApplicationService $applicationService,
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

        $command = UpdatePayrollDeductionCommand::create(
            id: (int) $id,
            employeeId: $params->get('employee_id') !== null ? (int) $params->get('employee_id') : null,
            deductionTypeId: $params->get('deduction_type_id') !== null ? (int) $params->get('deduction_type_id') : null,
            employeeDebtId: $params->get('employee_debt_id') !== null ? (int) $params->get('employee_debt_id') : null,
            period: $params->get('period') !== null ? (int) $params->get('period') : null,
            amount: $params->get('amount') !== null ? (float) $params->get('amount') : null,
            description: $params->get('description'),
            status: $params->get('status') !== null ? (int) $params->get('status') : null,
            detailInfo: $params->get('detail_info'),
            lockVersion: $params->get('lock_version') !== null ? (int) $params->get('lock_version') : null,
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
