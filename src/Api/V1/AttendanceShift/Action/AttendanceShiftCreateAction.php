<?php

declare(strict_types=1);

namespace App\Api\V1\AttendanceShift\Action;

// Application Layer
use App\Application\AttendanceShift\AttendanceShiftApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AttendanceShift\Validation\AttendanceShiftInputValidator;
use App\Application\AttendanceShift\Command\CreateAttendanceShiftCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AttendanceShiftCreateAction 
{
    private const ALLOWED_KEYS = ['name', 'start_time', 'end_time', 'break_minutes', 'cross_day', 'note', 'status', 'sync_mdb'];

    public function __construct(
        private AttendanceShiftInputValidator $inputValidator,
        private AttendanceShiftApplicationService $applicationService,
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

        $command = CreateAttendanceShiftCommand::create(
            name: (string) $params->get('name'),
            startTime: $params->get('start_time'),
            endTime: $params->get('end_time'),
            breakMinutes: (int) $params->get('break_minutes', 0),
            crossDay: (int) $params->get('cross_day', 0),
            note: $params->get('note'),
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
