<?php

declare(strict_types=1);

namespace App\Api\V1\EmployeeDebt\Action;

// Application Layer
use App\Application\EmployeeDebt\EmployeeDebtApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\EmployeeDebt\Validation\EmployeeDebtInputValidator;
use App\Application\EmployeeDebt\Command\CreateEmployeeDebtCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class EmployeeDebtCreateAction 
{
    private const ALLOWED_KEYS = ['employee_id', 'deduction_type_id', 'amount', 'date', 'note', 'status', 'sync_mdb'];

    public function __construct(
        private EmployeeDebtInputValidator $inputValidator,
        private EmployeeDebtApplicationService $applicationService,
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

        $command = CreateEmployeeDebtCommand::create(
            employeeId: (int) $params->get('employee_id'),
            deductionTypeId: (int) $params->get('deduction_type_id'),
            amount: (float) $params->get('amount'),
            date: (string) $params->get('date'),
            note: $params->get('note'),
            status: (int) $params->get('status'),
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
