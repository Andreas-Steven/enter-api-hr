<?php

declare(strict_types=1);

namespace App\Api\V1\AttendanceReport\Action;

// Application Layer
use App\Application\AttendanceReport\AttendanceReportApplicationService;
use App\Application\Shared\Factory\SearchCriteriaFactory;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AttendanceReport\Validation\AttendanceReportInputValidator;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Request\RequestParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AttendanceReportDataAction
{
    private const ALLOWED_KEYS = ['id', 'name', 'period', 'employee_id', 'branch_id', 'division_id', 'position_id', 'employee_type_id', 'work_pattern_id', 'report_type', 'status'];
    private const ALLOWED_SORT = [
        'id' => 'id',
        'name' => 'name',
        'period' => 'period',
        'employee_id' => 'employee_id',
        'branch_id' => 'branch_id',
        'division_id' => 'division_id',
        'position_id' => 'position_id',
        'employee_type_id' => 'employee_type_id',
        'work_pattern_id' => 'work_pattern_id',
        'report_type' => 'report_type',
        'status' => 'status',
    ];

    public function __construct(
        private SearchCriteriaFactory $factory,
        private AttendanceReportInputValidator $inputValidator,
        private AttendanceReportApplicationService $applicationService,
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
