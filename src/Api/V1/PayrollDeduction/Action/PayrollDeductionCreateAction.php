<?php

declare(strict_types=1);

namespace App\Api\V1\PayrollDeduction\Action;

// Application Layer
use App\Application\PayrollDeduction\PayrollDeductionApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\PayrollDeduction\Validation\PayrollDeductionInputValidator;
use App\Application\PayrollDeduction\Command\CreatePayrollDeductionCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PayrollDeductionCreateAction
{
    private const ALLOWED_KEYS = [
        'employee_id',
        'deduction_type_id',
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
            context: ValidationContext::CREATE,
        );

        $command = CreatePayrollDeductionCommand::create(
            employeeId: (int) $params->get('employee_id'),
            deductionTypeId: (int) $params->get('deduction_type_id'),
            period: (int) $params->get('period'),
            amount: (int) $params->get('amount'),
            status: (int) $params->get('status'),
            employeeDebtId: null,
            description: $params->get('description'),
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
