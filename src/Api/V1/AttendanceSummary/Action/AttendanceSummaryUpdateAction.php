<?php

declare(strict_types=1);

namespace App\Api\V1\AttendanceSummary\Action;

// Application Layer
use App\Application\AttendanceSummary\AttendanceSummaryApplicationService;
use App\Application\AttendanceSummary\Command\UpdateAttendanceSummaryCommand;
// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AttendanceSummary\Validation\AttendanceSummaryInputValidator;
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
 * AttendanceSummary Update API Action
 */
final class AttendanceSummaryUpdateAction
{
    private const ALLOWED_KEYS = ['name', 'status', 'notes', 'lock_version'];

    public function __construct(
        private AttendanceSummaryInputValidator $inputValidator,
        private AttendanceSummaryApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        CurrentRoute $currentRoute,
    ): ResponseInterface {
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
                    ],
                ),
                httpCode: Status::NOT_FOUND,
            );
        }

        $payload->ensureExists(
            resource: $resource,
        );

        $params = $payload->getRawParams()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS,
            )->with('id', $id)
            ->sanitize();

        $this->inputValidator->validate(
            data: $params,
            context: ValidationContext::UPDATE,
        );

        $command = UpdateAttendanceSummaryCommand::create(
            id: (int) $id,
            name: $params->get('name') !== null ? (string) $params->get('name') : null,
            status: $params->get('status') !== null ? (int) $params->get('status') : null,
            notes: $params->get('notes') !== null ? (string) $params->get('notes') : null,
            detailInfo: $params->get('detail_info'),
            lockVersion: $params->get('lock_version') !== null ? (int) $params->get('lock_version') : null,
        );

        $response = $this->applicationService->update(
            id: (int) $id,
            command: $command,
        );

        return $this->responseFactory->success(
            data: $response->toArray(),
            translate: Message::create(
                key: 'resource.updated',
                params: [
                    'resource' => $resource,
                ],
            ),
        );
    }
}
