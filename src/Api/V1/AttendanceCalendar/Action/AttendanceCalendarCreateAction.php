<?php

declare(strict_types=1);

namespace App\Api\V1\AttendanceCalendar\Action;

// Application Layer
use App\Application\AttendanceCalendar\AttendanceCalendarApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AttendanceCalendar\Validation\AttendanceCalendarInputValidator;
use App\Application\AttendanceCalendar\Command\CreateAttendanceCalendarCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AttendanceCalendarCreateAction 
{
    private const ALLOWED_KEYS = ['name', 'date', 'holiday_type', 'scope', 'status', 'sync_mdb', 'note', 'day_off'];

    public function __construct(
        private AttendanceCalendarInputValidator $inputValidator,
        private AttendanceCalendarApplicationService $applicationService,
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

        $command = CreateAttendanceCalendarCommand::create(
            name: (string) $params->get('name'),
            date: (string) $params->get('date'),
            holidayType: (string) $params->get('holiday_type'),
            scope: $params->get('scope'),
            status: (int) $params->get('status'),
            detailInfo: $params->get('detail_info'),
            note: $params->get('note'),
            dayOff: (int) $params->get('day_off', 0)
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
