<?php

declare(strict_types=1);

namespace App\Api\V1\AttendanceSummary\Action;

// Application Layer
use App\Application\AttendanceSummary\AttendanceSummaryApplicationService;
use App\Application\Shared\Factory\SearchCriteriaFactory;
// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AttendanceSummary\Validation\AttendanceSummaryInputValidator;
// Shared Layer
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AttendanceSummaryDataAction
{
    private const ALLOWED_KEYS = ['id', 'name', 'period', 'employee_id', 'status'];
    private const ALLOWED_SORT = [
        'id' => 'id',
        'name' => 'name',
        'period' => 'period',
        'status' => 'status',
    ];

    public function __construct(
        private SearchCriteriaFactory $factory,
        private AttendanceSummaryInputValidator $inputValidator,
        private AttendanceSummaryApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var \App\Shared\Request\RequestParams $payload */
        $payload = $request->getAttribute('payload');

        $filter = $payload->getFilter()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS,
            );

        $this->inputValidator->validate(
            data: $filter,
            context: ValidationContext::SEARCH,
        );

        $criteria = $this->factory->createFromRequest(
            params: $payload,
            allowedSort: self::ALLOWED_SORT,
        );

        $resource = $this->applicationService->getResource();
        $result = $this->applicationService->list(criteria: $criteria);

        return $this->responseFactory->success(
            data: $result->data,
            translate: Message::create(
                key: 'resource.list_retrieved',
                params: [
                    'resource' => $resource,
                ],
            ),
            meta: $result->getMeta(),
        );
    }
}
