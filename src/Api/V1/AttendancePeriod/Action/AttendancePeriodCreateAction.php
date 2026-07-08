<?php

declare(strict_types=1);

namespace App\Api\V1\AttendancePeriod\Action;

// Application Layer
use App\Application\AttendancePeriod\AttendancePeriodApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AttendancePeriod\Validation\AttendancePeriodInputValidator;
use App\Application\AttendancePeriod\Command\CreateAttendancePeriodCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AttendancePeriodCreateAction 
{
    private const ALLOWED_KEYS = ['year', 'status', 'sync_mdb'];

    public function __construct(
        private AttendancePeriodInputValidator $inputValidator,
        private AttendancePeriodApplicationService $applicationService,
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

        $command = CreateAttendancePeriodCommand::create(
            year: (int) $params->get('year'),
            status: (int) $params->get('status'),
            detailInfo: null
        );

        $resource = $this->applicationService->getResource();
        $response = $this->applicationService->create(command: $command);

        return $this->responseFactory->success(
            data: $response,
            translate: Message::create(
                key: 'resource.created',
                params: [
                    'resource' => $resource
                ]
            )
        );
    }
}
