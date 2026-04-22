<?php

declare(strict_types=1);

namespace App\Api\V1\AttendancePermission\Action;

// Application Layer
use App\Application\AttendancePermission\AttendancePermissionApplicationService;
use App\Application\AttendancePermission\Command\RejectAttendancePermissionCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AttendancePermission\Validation\AttendancePermissionInputValidator;

// Shared Layer
use App\Domain\Shared\Enums\ApprovalStatus;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// Vendor Layer
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;

/**
 * AttendancePermission Reject API Action
 */
final class AttendancePermissionRejectAction
{
    private const ALLOWED_KEYS = ['lock_version'];

    public function __construct(
        private AttendancePermissionInputValidator $inputValidator,
        private AttendancePermissionApplicationService $applicationService,
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

        // $payload?->ensureExists(
        //     resource: $resource
        // );

        $params = $payload?->getRawParams()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS
            )->with('id', $id)
            ->sanitize();

        $this->inputValidator->validate(
            data: $params,
            context: (string) ApprovalStatus::REJECTED->value,
        );

        $command = RejectAttendancePermissionCommand::create(
            id: (int) $id,
            lockVersion: $params->get('lock_version'),
        );

        $response = $this->applicationService->reject(
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
