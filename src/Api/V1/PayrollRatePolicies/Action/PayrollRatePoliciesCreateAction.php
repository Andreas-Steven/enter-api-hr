<?php

declare(strict_types=1);

namespace App\Api\V1\PayrollRatePolicies\Action;

// Application Layer
use App\Application\PayrollRatePolicies\PayrollRatePoliciesApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\PayrollRatePolicies\Validation\PayrollRatePoliciesInputValidator;
use App\Application\PayrollRatePolicies\Command\CreatePayrollRatePoliciesCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PayrollRatePoliciesCreateAction
{
    private const ALLOWED_KEYS = ['name', 'rate_type', 'deduction_type_id', 'amount', 'status', 'sync_mdb'];

    public function __construct(
        private PayrollRatePoliciesInputValidator $inputValidator,
        private PayrollRatePoliciesApplicationService $applicationService,
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

        $command = CreatePayrollRatePoliciesCommand::create(
            name: (string) $params->get('name'),
            rateType: (string) $params->get('rate_type'),
            deductionTypeId: $params->get('deduction_type_id') !== null ? (int) $params->get('deduction_type_id') : null,
            amount: (int) $params->get('amount'),
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
