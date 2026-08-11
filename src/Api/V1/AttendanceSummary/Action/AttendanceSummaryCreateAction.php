<?php

declare(strict_types=1);

namespace App\Api\V1\AttendanceSummary\Action;

// Application Layer
use App\Application\AttendanceSummary\AttendanceSummaryApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AttendanceSummary\Validation\AttendanceSummaryInputValidator;
use App\Application\AttendanceSummary\Command\GenerateAttendanceSummaryCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AttendanceSummaryCreateAction 
{
    private const ALLOWED_KEYS = [
        'period',
        'notes',
        'status',
        'detail_info',
        'sync_mdb',
    ];

    public function __construct(
        private AttendanceSummaryInputValidator $inputValidator,
        private AttendanceSummaryApplicationService $applicationService,
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

        $command = GenerateAttendanceSummaryCommand::create(
            period: (int) $params->get('period'),
            status: (int) $params->get('status'),
            notes: $params->get('notes'),
            detailInfo: $params->get('detail_info')
        );

        $resource = $this->applicationService->getResource();
        $result = $this->applicationService->generateAllFromAttendance(command: $command);

        return $this->responseFactory->success(
            data: $result->toArray(),
            translate: Message::create(
                key: 'resource.created',
                params: [
                    'resource' => $resource
                ]
            )
        );
    }
}
