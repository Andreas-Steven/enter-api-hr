<?php

declare(strict_types=1);

namespace App\Api\V1\PayrollReports\Action;

// Application Layer
use App\Application\PayrollReports\PayrollReportsApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\PayrollReports\Validation\PayrollReportsInputValidator;
use App\Application\PayrollReports\Command\CreatePayrollReportsCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PayrollReportsCreateAction 
{
    private const ALLOWED_KEYS = [
        'attendance_summary_id',
        'payroll_summary',
        'notes',
        'status',
        'detail_info',
        'sync_mdb',
    ];

    public function __construct(
        private PayrollReportsInputValidator $inputValidator,
        private PayrollReportsApplicationService $applicationService,
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

        $command = CreatePayrollReportsCommand::create(
            name: 'PR-' . date('YmdHis'),
            attendanceSummaryId: (int) $params->get('attendance_summary_id'),
            status: (int) $params->get('status'),
            payrollSummary: $params->get('payroll_summary'),
            notes: $params->get('notes'),
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
