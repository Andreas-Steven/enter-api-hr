<?php

declare(strict_types=1);

namespace App\Api\V1\EmployeeDebtTransaction\Action;

// Application Layer
use App\Application\EmployeeDebtTransaction\EmployeeDebtTransactionApplicationService;
use App\Application\EmployeeDebtTransaction\Command\UpdateEmployeeDebtTransactionCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\EmployeeDebtTransaction\Validation\EmployeeDebtTransactionInputValidator;

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
 * EmployeeDebtTransaction Update API Action
 */
final class EmployeeDebtTransactionUpdateAction
{
    private const ALLOWED_KEYS = [
        'employee_debt_id',
        'employee_id',
        'transaction_type',
        'date',
        'amount',
        'payment_source',
        'payroll_deduction_id',
        'payroll_period',
        'note',
        'status',
        'detail_info',
        'lock_version',
    ];

    public function __construct(
        private EmployeeDebtTransactionInputValidator $inputValidator,
        private EmployeeDebtTransactionApplicationService $applicationService,
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

        $command = UpdateEmployeeDebtTransactionCommand::create(
            id: (int) $id,
            employeeDebtId: $params->get('employee_debt_id') !== null ? (int) $params->get('employee_debt_id') : null,
            employeeId: $params->get('employee_id') !== null ? (int) $params->get('employee_id') : null,
            transactionType: $params->get('transaction_type'),
            date: $params->get('date'),
            amount: $params->get('amount') !== null ? (int) $params->get('amount') : null,
            paymentSource: $params->get('payment_source') ?: null,
            payrollDeductionId: $params->get('payroll_deduction_id') !== null ? (int) $params->get('payroll_deduction_id') : null,
            payrollPeriod: $params->get('payroll_period') !== null ? (int) $params->get('payroll_period') : null,
            note: $params->get('note'),
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
