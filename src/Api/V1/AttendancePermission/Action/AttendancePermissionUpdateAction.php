<?php

declare(strict_types=1);

namespace App\Api\V1\AttendancePermission\Action;

// Application Layer
use App\Application\AttendancePermission\AttendancePermissionApplicationService;
use App\Application\AttendancePermission\Command\UpdateAttendancePermissionCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AttendancePermission\Validation\AttendancePermissionInputValidator;

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
 * AttendancePermission Update API Action
 */
final class AttendancePermissionUpdateAction
{
    private const ALLOWED_KEYS = ['employee_id', 'permission_type', 'date', 'note', 'revision', 'status', 'lock_version'];

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

        $command = UpdateAttendancePermissionCommand::create(
            id: (int) $id,
            employeeId: $params->get('employee_id'),
            permissionType: $params->get('permission_type'),
            date: $params->get('date'),
            note: $params->get('note'),
            approved: null,
            approvedNote: null,
            status: (int) $params->get('status'),
            revision: $params->get('revision'),
            detailInfo: null,
            lockVersion: (int) $params->get('lock_version'),
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
