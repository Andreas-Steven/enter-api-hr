<?php

declare(strict_types=1);

namespace App\Api\V1\AttendanceDevice\Action;

// Application Layer
use App\Application\AttendanceDevice\AttendanceDeviceApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AttendanceDevice\Validation\AttendanceDeviceInputValidator;
use App\Application\AttendanceDevice\Command\CreateAttendanceDeviceCommand;
use App\Domain\AttendanceDevice\ValueObject\DeviceLocation;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AttendanceDeviceCreateAction 
{
    private const ALLOWED_KEYS = ['name', 'code', 'ip_address', 'address', 'requires_location', 'location', 'status', 'sync_mdb'];

    public function __construct(
        private AttendanceDeviceInputValidator $inputValidator,
        private AttendanceDeviceApplicationService $applicationService,
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

        $code = $params->get('code');
        if ($code !== null) {
            $params = $params->with('code', strtoupper((string) $code));
        }

        $this->inputValidator->validate(
            data: $params,
            context: ValidationContext::CREATE,
        );

        $command = new CreateAttendanceDeviceCommand(
            name: (string) $params->get('name'),
            code: (string) $params->get('code'),
            ipAddress: (string) $params->get('ip_address'),
            address: (string) $params->get('address'),
            requiresLocation: (int) $params->get('requires_location'),
            location: $params->get('location'),
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
