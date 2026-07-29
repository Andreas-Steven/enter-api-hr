<?php

declare(strict_types=1);

namespace App\Api\V1\PayrollDeductionType\Action;

// Application Layer
use App\Application\PayrollDeductionType\PayrollDeductionTypeApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\PayrollDeductionType\Validation\PayrollDeductionTypeInputValidator;
use App\Application\PayrollDeductionType\Command\CreatePayrollDeductionTypeCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PayrollDeductionTypeCreateAction 
{
    private const ALLOWED_KEYS = [
        'name',
        'category',
        'creates_debt',
        'status',
        'detail_info',
        'sync_mdb',
    ];

    public function __construct(
        private PayrollDeductionTypeInputValidator $inputValidator,
        private PayrollDeductionTypeApplicationService $applicationService,
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

        $command = CreatePayrollDeductionTypeCommand::create(
            name: (string) $params->get('name'),
            category: (string) $params->get('category'),
            createsDebt: (int) $params->get('creates_debt'),
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
