<?php

declare(strict_types=1);

namespace App\Api\V1\Employee\Action;

// Application Layer
use App\Application\Employee\EmployeeApplicationService;
use App\Application\Employee\Command\UpdateEmployeeCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\Employee\Validation\EmployeeInputValidator;

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
 * Employee Update API Action
 */
final class EmployeeUpdateAction
{
    private const ALLOWED_KEYS = [
        'name',
        'username',
        'division_id', 
        'position_id', 
        'employee_type_id', 
        'branch_id',
        'grade_id',
        'nik',
        'email', 
        'birth_date', 
        'date_start',
        'contact', 
        'image',
        'status',
        'lock_version',
    ];

    public function __construct(
        private EmployeeInputValidator $inputValidator,
        private EmployeeApplicationService $applicationService,
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

        $command = UpdateEmployeeCommand::create(
            id: (int) $id,
            name: $params->get('name'),
            username: $params->get('username'),
            divisionId: $params->has('division_id') ? (int) $params->get('division_id') : null,
            positionId: $params->has('position_id') ? (int) $params->get('position_id') : null,
            employeeTypeId: $params->has('employee_type_id') ? (int) $params->get('employee_type_id') : null,
            branchId: $params->has('branch_id') ? (int) $params->get('branch_id') : null,
            gradeId: $params->has('grade_id') ? (int) $params->get('grade_id') : null,
            nik: $params->get('nik'),
            email: $params->get('email'),
            birthDate: $params->get('birth_date'),
            dateStart: $params->get('date_start'),
            contact: $params->get('contact'),
            image: $params->get('image'),
            status: $params->has('status') ? (int) $params->get('status') : null,
            detailInfo: $params->get('detail_info'),
            lockVersion: $params->has('lock_version') ? (int) $params->get('lock_version') : null,
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
