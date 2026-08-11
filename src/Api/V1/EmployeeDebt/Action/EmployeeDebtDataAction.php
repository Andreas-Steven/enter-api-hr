<?php

declare(strict_types=1);

namespace App\Api\V1\EmployeeDebt\Action;

// Application Layer
use App\Application\EmployeeDebt\EmployeeDebtApplicationService;
use App\Application\Shared\Factory\SearchCriteriaFactory;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\EmployeeDebt\Validation\EmployeeDebtInputValidator;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Request\RequestParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class EmployeeDebtDataAction
{
    private const ALLOWED_KEYS = [
        'id',
        'name',
        'employee_id',
        'deduction_type_id',
        'amount',
        'date',
        'note',
        'approved',
        'approved_note',
        'remaining_amount',
        'paid_amount',
        'is_settled',
        'settled_at',
        'status',
    ];
    private const ALLOWED_SORT = [
        'id' => 'id',
        'name' => 'name',
        'employee_id' => 'employee_id',
        'deduction_type_id' => 'deduction_type_id',
        'amount' => 'amount',
        'date' => 'date',
        'note' => 'note',
        'approved' => 'approved',
        'approved_note' => 'approved_note',
        'remaining_amount' => 'remaining_amount',
        'paid_amount' => 'paid_amount',
        'is_settled' => 'is_settled',
        'settled_at' => 'settled_at',
        'status' => 'status',
    ];

    public function __construct(
        private SearchCriteriaFactory $factory,
        private EmployeeDebtInputValidator $inputValidator,
        private EmployeeDebtApplicationService $applicationService,
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

        // Convert integer to string for name field
        // $filterArray = $filter->toArray();
        // if (isset($filterArray['name']) && is_int($filterArray['name'])) {
        //     $filterArray['name'] = (string) $filterArray['name'];
        //     $filter = new \App\Shared\Request\RawParams($filterArray);
        // }

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
