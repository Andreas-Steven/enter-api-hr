<?php

declare(strict_types=1);

namespace App\Api\V1\EmployeeDebtInstallment\Action;

// Application Layer
use App\Application\EmployeeDebtInstallment\EmployeeDebtInstallmentApplicationService;
use App\Application\EmployeeDebtInstallment\Command\UpdateEmployeeDebtInstallmentCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\EmployeeDebtInstallment\Validation\EmployeeDebtInstallmentInputValidator;

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
 * EmployeeDebtInstallment Update API Action
 */
final class EmployeeDebtInstallmentUpdateAction
{
    private const ALLOWED_KEYS = [
        'employee_debt_id',
        'payroll_deduction_id',
        'sequence',
        'due_date',
        'amount',
        'payment_status',
        'status',
        'detail_info',
        'lock_version',
    ];

    public function __construct(
        private EmployeeDebtInstallmentInputValidator $inputValidator,
        private EmployeeDebtInstallmentApplicationService $applicationService,
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

        $command = UpdateEmployeeDebtInstallmentCommand::create(
            id: (int) $id,
            employeeDebtId: (int) $params->get('employee_debt_id'),
            payrollDeductionId: (int) $params->get('payroll_deduction_id'),
            sequence: (int) $params->get('sequence'),
            dueDate: (string) $params->get('due_date'),
            amount: (float) $params->get('amount'),
            paymentStatus: (int) $params->get('payment_status'),
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
