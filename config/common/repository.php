<?php

declare(strict_types=1);

use App\Domain\Example\Repository\ExampleRepositoryInterface;
use App\Domain\EmployeeDebt\Repository\EmployeeDebtRepositoryInterface;
use App\Domain\DeductionType\Repository\DeductionTypeRepositoryInterface;
use App\Domain\AttendanceReport\Repository\AttendanceReportRepositoryInterface;
use App\Domain\AttendancePeriod\Repository\AttendancePeriodRepositoryInterface;
use App\Domain\AttendanceShift\Repository\AttendanceShiftRepositoryInterface;
use App\Domain\AttendanceCalendar\Repository\AttendanceCalendarRepositoryInterface;
use App\Domain\AttendancePolicies\Repository\AttendancePoliciesRepositoryInterface;
use App\Domain\EmployeeGrade\Repository\EmployeeGradeRepositoryInterface;
use App\Domain\EmployeeLeaveType\Repository\EmployeeLeaveTypeRepositoryInterface;
use App\Domain\EmployeeLeave\Repository\EmployeeLeaveRepositoryInterface;
use App\Domain\Branch\Repository\BranchRepositoryInterface;
use App\Domain\GeneralAffair\Repository\GeneralAffairRepositoryInterface;
use App\Domain\GeneralAffairLocation\Repository\GeneralAffairLocationRepositoryInterface;
use App\Domain\GeneralAffairCategory\Repository\GeneralAffairCategoryRepositoryInterface;
use App\Domain\GeneralAffairProduct\Repository\GeneralAffairProductRepositoryInterface;
use App\Domain\Attendance\Repository\AttendanceRepositoryInterface;
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;
use App\Domain\EmployeeType\Repository\EmployeeTypeRepositoryInterface;
use App\Domain\EmployeePosition\Repository\EmployeePositionRepositoryInterface;
use App\Domain\EmployeeDivision\Repository\EmployeeDivisionRepositoryInterface;
use App\Domain\AttendancePermission\Repository\AttendancePermissionRepositoryInterface;
use App\Domain\WorkPattern\Repository\WorkPatternRepositoryInterface;
use App\Infrastructure\Persistence\Example\ExampleRepository;
use App\Infrastructure\Persistence\EmployeeDebt\EmployeeDebtRepository;
use App\Infrastructure\Persistence\DeductionType\DeductionTypeRepository;
use App\Infrastructure\Persistence\AttendanceReport\AttendanceReportRepository;
use App\Infrastructure\Persistence\AttendancePeriod\AttendancePeriodRepository;
use App\Infrastructure\Persistence\AttendanceShift\AttendanceShiftRepository;
use App\Infrastructure\Persistence\AttendanceCalendar\AttendanceCalendarRepository;
use App\Infrastructure\Persistence\AttendancePolicies\AttendancePoliciesRepository;
use App\Infrastructure\Persistence\EmployeeGrade\EmployeeGradeRepository;
use App\Infrastructure\Persistence\EmployeeLeave\EmployeeLeaveRepository;
use App\Infrastructure\Persistence\Branch\BranchRepository;
use App\Infrastructure\Persistence\GeneralAffair\GeneralAffairRepository;
use App\Infrastructure\Persistence\GeneralAffairLocation\GeneralAffairLocationRepository;
use App\Infrastructure\Persistence\GeneralAffairCategory\GeneralAffairCategoryRepository;
use App\Infrastructure\Persistence\GeneralAffairProduct\GeneralAffairProductRepository;
use App\Infrastructure\Persistence\Attendance\AttendanceRepository;
use App\Infrastructure\Persistence\Employee\EmployeeRepository;
use App\Infrastructure\Persistence\EmployeeType\EmployeeTypeRepository;
use App\Infrastructure\Persistence\EmployeePosition\EmployeePositionRepository;
use App\Infrastructure\Persistence\EmployeeDivision\EmployeeDivisionRepository;
use App\Infrastructure\Persistence\AttendancePermission\AttendancePermissionRepository;
use App\Infrastructure\Persistence\WorkPattern\WorkPatternRepository;
use App\Domain\AttendanceDevice\Repository\AttendanceDeviceRepositoryInterface;
use App\Infrastructure\Persistence\AttendanceDevice\AttendanceDeviceRepository;
use App\Shared\ValueObject\LockVersionConfig;
use App\Infrastructure\Security\CurrentUser;
use Yiisoft\Definitions\Reference;

return [
    ExampleRepositoryInterface::class => [
        'class' => ExampleRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    AttendanceDeviceRepositoryInterface::class => [
        'class' => AttendanceDeviceRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    WorkPatternRepositoryInterface::class => [
        'class' => WorkPatternRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    AttendancePermissionRepositoryInterface::class => [
        'class' => AttendancePermissionRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    EmployeeDivisionRepositoryInterface::class => [
        'class' => EmployeeDivisionRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    EmployeePositionRepositoryInterface::class => [
        'class' => EmployeePositionRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    EmployeeTypeRepositoryInterface::class => [
        'class' => EmployeeTypeRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    EmployeeRepositoryInterface::class => [
        'class' => EmployeeRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    AttendanceRepositoryInterface::class => [
        'class' => AttendanceRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    GeneralAffairProductRepositoryInterface::class => [
        'class' => GeneralAffairProductRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    GeneralAffairCategoryRepositoryInterface::class => [
        'class' => GeneralAffairCategoryRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    GeneralAffairLocationRepositoryInterface::class => [
        'class' => GeneralAffairLocationRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    GeneralAffairRepositoryInterface::class => [
        'class' => GeneralAffairRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    BranchRepositoryInterface::class => [
        'class' => BranchRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    EmployeeLeaveRepositoryInterface::class => [
        'class' => EmployeeLeaveRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    EmployeeGradeRepositoryInterface::class => [
        'class' => EmployeeGradeRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    AttendancePoliciesRepositoryInterface::class => [
        'class' => AttendancePoliciesRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    AttendanceCalendarRepositoryInterface::class => [
        'class' => AttendanceCalendarRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    AttendanceShiftRepositoryInterface::class => [
        'class' => AttendanceShiftRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    AttendancePeriodRepositoryInterface::class => [
        'class' => AttendancePeriodRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    AttendanceReportRepositoryInterface::class => [
        'class' => AttendanceReportRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    DeductionTypeRepositoryInterface::class => [
        'class' => DeductionTypeRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
];