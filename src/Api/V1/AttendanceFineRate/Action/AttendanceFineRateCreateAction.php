<?php

declare(strict_types=1);

namespace App\Api\V1\AttendanceFineRate\Action;

// Application Layer
use App\Application\AttendanceFineRate\AttendanceFineRateApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AttendanceFineRate\Validation\AttendanceFineRateInputValidator;
use App\Application\AttendanceFineRate\Command\CreateAttendanceFineRateCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AttendanceFineRateCreateAction 
{
    private const ALLOWED_KEYS = ['name', 'incident_type', 'deduction_type_id', 'amount', 'status', 'sync_mdb'];

    public function __construct(
        private AttendanceFineRateInputValidator $inputValidator,
        private AttendanceFineRateApplicationService $applicationService,
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

        $command = CreateAttendanceFineRateCommand::create(
            name: (string) $params->get('name'),
            incidentType: (string) $params->get('incident_type'),
            deductionTypeId: (int) $params->get('deduction_type_id'),
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
