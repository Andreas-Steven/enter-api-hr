<?php

declare(strict_types=1);

namespace App\Api\V1\EmployeeDebtInstallment\Action;

// Application Layer
use App\Application\EmployeeDebtInstallment\EmployeeDebtInstallmentApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\EmployeeDebtInstallment\Validation\EmployeeDebtInstallmentInputValidator;
use App\Application\EmployeeDebtInstallment\Command\CreateEmployeeDebtInstallmentCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class EmployeeDebtInstallmentCreateAction 
{
    private const ALLOWED_KEYS = [
        'employee_debt_id',
        'payroll_deduction_id',
        'sequence',
        'due_date',
        'amount',
        'status',
        'detail_info',
        'sync_mdb',
    ];

    public function __construct(
        private EmployeeDebtInstallmentInputValidator $inputValidator,
        private EmployeeDebtInstallmentApplicationService $applicationService,
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

        $command = CreateEmployeeDebtInstallmentCommand::create(
            employeeDebtId: (int) $params->get('employee_debt_id'),
            dueDate: (string) $params->get('due_date'),
            amount: (float) $params->get('amount'),
            status: (int) $params->get('status'),
            payrollDeductionId: (int) $params->get('payroll_deduction_id'),
            sequence: (int) $params->get('sequence'),
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
