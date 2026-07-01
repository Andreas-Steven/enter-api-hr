<?php

declare(strict_types=1);

namespace App\Api\V1\Employee\Action;

// Application Layer
use App\Application\Employee\EmployeeApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\Employee\Validation\EmployeeInputValidator;
use App\Application\Employee\Command\CreateEmployeeCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;
use App\Domain\Shared\Enums\EmployeeDefault;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class EmployeeCreateAction 
{
    private const ALLOWED_KEYS = ['name', 'username', 'sso_id', 'branch_id', 'status', 'sync_mdb'];

    public function __construct(
        private EmployeeInputValidator $inputValidator,
        private EmployeeApplicationService $applicationService,
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

        $command = CreateEmployeeCommand::create(
            name: (string) $params->get('name'),
            username: (string) $params->get('username'),
            ssoId: (int) $params->get('sso_id'),
            divisionId: EmployeeDefault::DIVISION_ID,
            positionId: EmployeeDefault::POSITION_ID,
            employeeTypeId: EmployeeDefault::EMPLOYEE_TYPE_ID,
            branchId: EmployeeDefault::BRANCH_ID,
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
