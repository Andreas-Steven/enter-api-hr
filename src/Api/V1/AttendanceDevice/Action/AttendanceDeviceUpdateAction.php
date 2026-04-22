<?php

declare(strict_types=1);

namespace App\Api\V1\AttendanceDevice\Action;

// Application Layer
use App\Application\AttendanceDevice\AttendanceDeviceApplicationService;
use App\Application\AttendanceDevice\Command\UpdateAttendanceDeviceCommand;
use App\Domain\AttendanceDevice\ValueObject\DeviceLocation;
use App\Domain\AttendanceDevice\ValueObject\IpAddress;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AttendanceDevice\Validation\AttendanceDeviceInputValidator;

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
 * AttendanceDevice Update API Action
 */
final class AttendanceDeviceUpdateAction
{
    private const ALLOWED_KEYS = ['name', 'code', 'ip_address', 'address', 'requires_location', 'location', 'status', 'sync_mdb'];


    public function __construct(
        private AttendanceDeviceInputValidator $inputValidator,
        private AttendanceDeviceApplicationService $applicationService,
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
            resource: $resource,
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

        $command = new UpdateAttendanceDeviceCommand(
            id: (int) $id,
            name: $params->get('name'),
            code: $params->get('code'),
            address: $params->get('address'),
            ipAddress: $params->get('ip_address'),
            location: $params->get('location'),
            status: $params->get('status'),
            detailInfo: $params->get('detail_info'),
            lockVersion: $params->get('lock_version'),
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
