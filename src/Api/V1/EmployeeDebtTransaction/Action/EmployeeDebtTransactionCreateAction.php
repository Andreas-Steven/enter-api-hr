<?php

declare(strict_types=1);

namespace App\Api\V1\EmployeeDebtTransaction\Action;

// Application Layer
use App\Application\EmployeeDebtTransaction\EmployeeDebtTransactionApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\EmployeeDebtTransaction\Validation\EmployeeDebtTransactionInputValidator;
use App\Application\EmployeeDebtTransaction\Command\CreateEmployeeDebtTransactionCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class EmployeeDebtTransactionCreateAction 
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
        'sync_mdb',
    ];

    public function __construct(
        private EmployeeDebtTransactionInputValidator $inputValidator,
        private EmployeeDebtTransactionApplicationService $applicationService,
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

        $command = CreateEmployeeDebtTransactionCommand::create(
            employeeDebtId: (int) $params->get('employee_debt_id'),
            type: (string) $params->get('type'),
            amount: (float) $params->get('amount'),
            transactionDate: (string) $params->get('transaction_date'),
            status: (int) $params->get('status'),
            employeeDebtInstallmentId: (int) $params->get('employee_debt_installment_id'),
            payrollDeductionId: (int) $params->get('payroll_deduction_id'),
            note: $params->get('note'),
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
