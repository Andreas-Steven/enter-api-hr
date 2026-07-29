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
        'employee_debt_installment_id',
        'payroll_deduction_id',
        'type',
        'amount',
        'transaction_date',
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
            employeeDebtId: (int) $params->get('employee_debt_id'),
            employeeDebtInstallmentId: (int) $params->get('employee_debt_installment_id'),
            payrollDeductionId: (int) $params->get('payroll_deduction_id'),
            type: (string) $params->get('type'),
            amount: (float) $params->get('amount'),
            transactionDate: (string) $params->get('transaction_date'),
            note: $params->get('note'),
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
