<?php

declare(strict_types=1);

namespace App\Api\V1\AttendanceReport\Action;

// Application Layer
use App\Application\AttendanceReport\AttendanceReportApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AttendanceReport\Validation\AttendanceReportInputValidator;
use App\Application\AttendanceReport\Command\CreateAttendanceReportCommand;
use App\Application\AttendanceReport\Dto\AttendanceReportResponse;

// Shared Layer
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AttendanceReportCreateAction
{
    private const ALLOWED_KEYS = ['period'];

    public function __construct(
        private AttendanceReportInputValidator $inputValidator,
        private AttendanceReportApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var \App\Shared\Request\RequestParams $payload */
        $payload = $request->getAttribute('payload');

        $params = $payload->getRawParams()
            ->onlyAllowed(allowedKeys: self::ALLOWED_KEYS)
            ->sanitize();

        $this->inputValidator->validate(
            data: $params,
            context: ValidationContext::CREATE,
        );

        $command = CreateAttendanceReportCommand::create(
            period: (int) $params->get('period'),
        );

        $resource = $this->applicationService->getResource();
        $reports = $this->applicationService->create(command: $command);

        return $this->responseFactory->success(
            data: array_map(
                static fn(AttendanceReportResponse $response): array => $response->toArray(),
                $reports
            ),
            translate: Message::create(
                key: 'resource.created',
                params: [
                    'resource' => $resource
                ]
            )
        );
    }
}
