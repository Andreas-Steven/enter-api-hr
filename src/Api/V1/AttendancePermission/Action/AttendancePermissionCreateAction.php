<?php

declare(strict_types=1);

namespace App\Api\V1\AttendancePermission\Action;

// Application Layer
use App\Application\AttendancePermission\AttendancePermissionApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AttendancePermission\Validation\AttendancePermissionInputValidator;
use App\Application\AttendancePermission\Command\CreateAttendancePermissionCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AttendancePermissionCreateAction 
{
    private const ALLOWED_KEYS = ['employee_id', 'permission_type', 'date', 'note', 'revision', 'status', 'detail_info', 'sync_mdb'];

    public function __construct(
        private AttendancePermissionInputValidator $inputValidator,
        private AttendancePermissionApplicationService $applicationService,
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

        $command = CreateAttendancePermissionCommand::create(
            // name: (string) $params->get('name'),
            employeeId: (int) $params->get('employee_id'),
            permissionType: (int) $params->get('permission_type'),
            date: $params->get('date'),
            note: (string) $params->get('note'),
            revision: $params->get('revision'),
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
