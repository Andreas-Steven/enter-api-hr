<?php

declare(strict_types=1);

namespace App\Api\V1\EmployeeDebtTransaction\Action;

// Application Layer
use App\Application\EmployeeDebtTransaction\EmployeeDebtTransactionApplicationService;
use App\Application\Shared\Factory\SearchCriteriaFactory;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\EmployeeDebtTransaction\Validation\EmployeeDebtTransactionInputValidator;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class EmployeeDebtTransactionDataAction
{
    private const ALLOWED_KEYS = [
        'id',
        'name',
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
    ];
    private const ALLOWED_SORT = [
        'id' => 'id',
        'name' => 'name',
        'employee_debt_id' => 'employee_debt_id',
        'employee_id' => 'employee_id',
        'transaction_type' => 'transaction_type',
        'date' => 'date',
        'amount' => 'amount',
        'payment_source' => 'payment_source',
        'payroll_deduction_id' => 'payroll_deduction_id',
        'payroll_period' => 'payroll_period',
        'status' => 'status',
    ];

    public function __construct(
        private SearchCriteriaFactory $factory,
        private EmployeeDebtTransactionInputValidator $inputValidator,
        private EmployeeDebtTransactionApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var \App\Shared\Request\RequestParams $payload */
        $payload = $request->getAttribute('payload');

        $filter = $payload->getFilter()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS
            )->with('status', RecordStatus::DRAFT->value);

        $this->inputValidator->validate(
            data: $filter,
            context: ValidationContext::SEARCH,
        );

        $criteria = $this->factory->createFromRequest(
            params: $payload,
            allowedSort: self::ALLOWED_SORT
        );

        $resource = $this->applicationService->getResource();
        $result = $this->applicationService->list(criteria: $criteria);

        return $this->responseFactory->success(
            data: $result->data,
            translate: Message::create(
                key: 'resource.list_retrieved',
                params: [
                    'resource' => $resource
                ]
            ),
            meta: $result->getMeta(),
        );
    }
}
