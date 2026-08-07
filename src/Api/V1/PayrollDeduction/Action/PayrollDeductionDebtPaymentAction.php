<?php

declare(strict_types=1);

namespace App\Api\V1\PayrollDeduction\Action;

// Application Layer
use App\Application\PayrollDeduction\PayrollDeductionApplicationService;
use App\Application\PayrollDeduction\Command\CreatePayrollDeductionFromDebtCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\PayrollDeduction\Validation\PayrollDeductionInputValidator;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * PayrollDeduction DebtPayment API Action
 *
 * Membuat potongan gaji yang terkait EmployeeDebt.
 * employee_id & deduction_type_id di-derive otomatis dari EmployeeDebt.
 */
final class PayrollDeductionDebtPaymentAction
{
    private const ALLOWED_KEYS = [
        'employee_debt_id',
        'period',
        'amount',
        'description',
        'status',
        'detail_info',
        'sync_mdb',
    ];

    public function __construct(
        private PayrollDeductionInputValidator $inputValidator,
        private PayrollDeductionApplicationService $applicationService,
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
            context: ValidationContext::PAYROLL_DEDUCTION_DEBT_PAYMENT,
        );

        $command = CreatePayrollDeductionFromDebtCommand::create(
            employeeDebtId: (int) $params->get('employee_debt_id'),
            period: (int) $params->get('period'),
            amount: (int) $params->get('amount'),
            status: (int) $params->get('status'),
            description: $params->get('description'),
            detailInfo: $params->get('detail_info')
        );

        $resource = $this->applicationService->getResource();
        $response = $this->applicationService->createFromDebt(command: $command);

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
