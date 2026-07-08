<?php

declare(strict_types=1);

namespace App\Api\V1\AttendanceCalendar\Action;

// Application Layer
use App\Application\AttendanceCalendar\AttendanceCalendarApplicationService;
use App\Application\AttendanceCalendar\Command\UpdateAttendanceCalendarCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AttendanceCalendar\Validation\AttendanceCalendarInputValidator;

// Shared Layer
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// Vendor Layer
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;

/**
 * AttendanceCalendar Update API Action
 */
final class AttendanceCalendarUpdateAction
{
    private const ALLOWED_KEYS = ['name', 'date', 'holiday_type', 'scope', 'status', 'lock_version', 'note', 'day_off'];

    public function __construct(
        private AttendanceCalendarInputValidator $inputValidator,
        private AttendanceCalendarApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        CurrentRoute $currentRoute,
    ): ResponseInterface
    {
        /** @var \App\Shared\Request\RequestParams|null $payload */
        $id = $currentRoute->getArgument('id');
        $payload = $request->getAttribute('payload');
        $resource = $this->applicationService->getResource();

        if ($id === null) {
            return $this->responseFactory->fail(
                translate: Message::create(
                    key: 'route.parameter_missing',
                    params: [
                        'resource' => $resource,
                        'parameter' => 'id',
                    ]
                ),
                httpCode: Status::NOT_FOUND
            );
        }

        $payload->ensureExists(
            resource: $resource
        );

        $params = $payload->getRawParams()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS
            )->with('id', $id)
            ->sanitize();

        $this->inputValidator->validate(
            data: $params,
            context: ValidationContext::UPDATE,
        );

        $command = UpdateAttendanceCalendarCommand::create(
            id: (int) $id,
            name: $params->get('name'),
            date: $params->get('date'),
            holidayType: $params->get('holiday_type'),
            scope: $params->get('scope'),
            status: (int) $params->get('status'),
            detailInfo: $params->get('detail_info'),
            lockVersion: (int) $params->get('lock_version'),
            note: $params->has('note') ? $params->get('note') : UpdateAttendanceCalendarCommand::UNSET,
            dayOff: $params->get('day_off'),
        );

        $response = $this->applicationService->update(
            id: (int) $id,
            command: $command
        );

        return $this->responseFactory->success(
            data: $response->toArray(),
            translate: Message::create(
                key: 'resource.updated',
                params: [
                    'resource' => $resource,
                ]
            ),
        );
    }
}
