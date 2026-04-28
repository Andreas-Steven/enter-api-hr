<?php

declare(strict_types=1);

namespace App\Api\V1\Attendance\Action;

// Application Layer
use App\Application\Attendance\AttendanceApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\Attendance\Validation\AttendanceInputValidator;
use App\Application\Attendance\Command\CreateAttendanceCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AttendanceCreateAction 
{
    private const ALLOWED_KEYS = ['date', 'attendance', 'status', 'sync_mdb'];

    public function __construct(
        private AttendanceInputValidator $inputValidator,
        private AttendanceApplicationService $applicationService,
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

        $command = CreateAttendanceCommand::create(
            date: (string) $params->get('date'),
            attendance: $params->get('attendance'),
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
