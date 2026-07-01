<?php

declare(strict_types=1);

namespace App\Api\V1\WorkPattern\Action;

// Application Layer
use App\Application\WorkPattern\WorkPatternApplicationService;
use App\Application\Shared\Factory\SearchCriteriaFactory;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\WorkPattern\Validation\WorkPatternInputValidator;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Request\RequestParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class WorkPatternDataAction
{
    private const ALLOWED_KEYS = ['id', 'name', 'working_days', 'status'];
    private const ALLOWED_SORT = [
        'id' => 'id',
        'name' => 'name',
        'working_days' => 'working_days',
        'status' => 'status',
    ];

    public function __construct(
        private SearchCriteriaFactory $factory,
        private WorkPatternInputValidator $inputValidator,
        private WorkPatternApplicationService $applicationService,
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
