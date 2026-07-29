<?php

declare(strict_types=1);

// Domain Layer
use App\Api;

// Api Layer
use App\Api\V1\Example\Action as ExampleV1;
use App\Api\V1\EmployeeDebtTransaction\Action as EmployeeDebtTransactionV1;
use App\Api\V1\EmployeeDebtInstallment\Action as EmployeeDebtInstallmentV1;
use App\Api\V1\PayrollDeduction\Action as PayrollDeductionV1;
use App\Api\V1\EmployeeDebt\Action as EmployeeDebtV1;
use App\Api\V1\PayrollDeductionType\Action as PayrollDeductionTypeV1;
use App\Api\V1\AttendanceReport\Action as AttendanceReportV1;
use App\Api\V1\AttendanceShift\Action as AttendanceShiftV1;
use App\Api\V1\AttendancePeriod\Action as AttendancePeriodV1;
use App\Api\V1\AttendanceCalendar\Action as AttendanceCalendarV1;
use App\Api\V1\AttendancePolicies\Action as AttendancePoliciesV1;
use App\Api\V1\EmployeeGrade\Action as EmployeeGradeV1;
use App\Api\V1\EmployeeLeave\Action as EmployeeLeaveV1;
use App\Api\V1\Branch\Action as BranchV1;
use App\Api\V1\GeneralAffair\Action as GeneralAffairV1;
use App\Api\V1\GeneralAffairLocation\Action as GeneralAffairLocationV1;
use App\Api\V1\GeneralAffairCategory\Action as GeneralAffairCategoryV1;
use App\Api\V1\GeneralAffairProduct\Action as GeneralAffairProductV1;
use App\Api\V1\Attendance\Action as AttendanceV1;
use App\Api\V1\Employee\Action as EmployeeV1;
use App\Api\V1\EmployeeType\Action as EmployeeTypeV1;
use App\Api\V1\EmployeePosition\Action as EmployeePositionV1;
use App\Api\V1\EmployeeDivision\Action as EmployeeDivisionV1;
use App\Api\V1\AttendancePermission\Action as AttendancePermissionV1;
use App\Api\V1\Shift\Action as ShiftV1;
use App\Api\V1\WorkPattern\Action as WorkPatternV1;
use App\Api\V1\AttendanceDevice\Action as AttendanceDeviceV1;

// Shared Layer
use App\Shared\Middleware\RequestParamsMiddleware;

// Vendor Layer
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

// @var array $params

return [
    Route::get('/')->action(Api\IndexAction::class)->name('app/index'),

    Group::create('/v1')
        ->middleware(RequestParamsMiddleware::class)
        ->routes(
            Route::get('/example')
                ->action(ExampleV1\ExampleDataAction::class)
                ->name('v1/example/index')
                ->defaults(['permission' => 'example.index']),
            Route::post('/example/data')
                ->action(ExampleV1\ExampleDataAction::class)
                ->name('v1/example/data')
                ->defaults(['permission' => 'example.data']),
            Route::get('/example/{id:\d+}')
                ->action(ExampleV1\ExampleViewAction::class)
                ->name('v1/example/view')
                ->defaults(['permission' => 'example.view']),
            Route::post('/example/create')
                ->action(ExampleV1\ExampleCreateAction::class)
                ->name('v1/example/create')
                ->defaults(['permission' => 'example.create']),
            Route::put('/example/{id:\d+}')
                ->action(ExampleV1\ExampleUpdateAction::class)
                ->name('v1/example/update')
                ->defaults(['permission' => 'example.update']),
            Route::delete('/example/{id:\d+}')
                ->action(ExampleV1\ExampleDeleteAction::class)
                ->name('v1/example/delete')
                ->defaults(['permission' => 'example.delete']),
            Route::post('/example/{id:\d+}/restore')
                ->action(ExampleV1\ExampleRestoreAction::class)
                ->name('v1/example/restore')
                ->defaults(['permission' => 'example.restore']),

            /**
             * Attendance Module
             */

            // AttendanceDevice Routes
            Route::get('/attendance-device')
                ->action(AttendanceDeviceV1\AttendanceDeviceDataAction::class)
                ->name('v1/attendance-device/index')
                ->defaults(['permission' => 'attendance-device.index']),
            Route::post('/attendance-device/data')
                ->action(AttendanceDeviceV1\AttendanceDeviceDataAction::class)
                ->name('v1/attendance-device/data')
                ->defaults(['permission' => 'attendance-device.data']),
            Route::get('/attendance-device/{id:\d+}')
                ->action(AttendanceDeviceV1\AttendanceDeviceViewAction::class)
                ->name('v1/attendance-device/view')
                ->defaults(['permission' => 'attendance-device.view']),
            Route::post('/attendance-device/create')
                ->action(AttendanceDeviceV1\AttendanceDeviceCreateAction::class)
                ->name('v1/attendance-device/create')
                ->defaults(['permission' => 'attendance-device.create']),
            Route::put('/attendance-device/{id:\d+}')
                ->action(AttendanceDeviceV1\AttendanceDeviceUpdateAction::class)
                ->name('v1/attendance-device/update')
                ->defaults(['permission' => 'attendance-device.update']),
            Route::delete('/attendance-device/{id:\d+}')
                ->action(AttendanceDeviceV1\AttendanceDeviceDeleteAction::class)
                ->name('v1/attendance-device/delete')
                ->defaults(['permission' => 'attendance-device.delete']),
            Route::post('/attendance-device/{id:\d+}/restore')
                ->action(AttendanceDeviceV1\AttendanceDeviceRestoreAction::class)
                ->name('v1/attendance-device/restore')
                ->defaults(['permission' => 'attendance-device.restore']),

            // WorkPattern Routes
            Route::get('/work-pattern')
                ->action(WorkPatternV1\WorkPatternDataAction::class)
                ->name('v1/work-pattern/index')
                ->defaults(['permission' => 'work-pattern.index']),
            Route::post('/work-pattern/data')
                ->action(WorkPatternV1\WorkPatternDataAction::class)
                ->name('v1/work-pattern/data')
                ->defaults(['permission' => 'work-pattern.data']),
            Route::get('/work-pattern/{id:\d+}')
                ->action(WorkPatternV1\WorkPatternViewAction::class)
                ->name('v1/work-pattern/view')
                ->defaults(['permission' => 'work-pattern.view']),
            Route::post('/work-pattern/create')
                ->action(WorkPatternV1\WorkPatternCreateAction::class)
                ->name('v1/work-pattern/create')
                ->defaults(['permission' => 'work-pattern.create']),
            Route::put('/work-pattern/{id:\d+}')
                ->action(WorkPatternV1\WorkPatternUpdateAction::class)
                ->name('v1/work-pattern/update')
                ->defaults(['permission' => 'work-pattern.update']),
            Route::delete('/work-pattern/{id:\d+}')
                ->action(WorkPatternV1\WorkPatternDeleteAction::class)
                ->name('v1/work-pattern/delete')
                ->defaults(['permission' => 'work-pattern.delete']),
            Route::post('/work-pattern/{id:\d+}/restore')
                ->action(WorkPatternV1\WorkPatternRestoreAction::class)
                ->name('v1/work-pattern/restore')
                ->defaults(['permission' => 'work-pattern.restore']),

            // AttendancePermission Routes
            Route::get('/attendance-permission')
                ->action(AttendancePermissionV1\AttendancePermissionDataAction::class)
                ->name('v1/attendance-permission/index')
                ->defaults(['permission' => 'attendance-permission.index']),
            Route::post('/attendance-permission/data')
                ->action(AttendancePermissionV1\AttendancePermissionDataAction::class)
                ->name('v1/attendance-permission/data')
                ->defaults(['permission' => 'attendance-permission.data']),
            Route::get('/attendance-permission/{id:\d+}')
                ->action(AttendancePermissionV1\AttendancePermissionViewAction::class)
                ->name('v1/attendance-permission/view')
                ->defaults(['permission' => 'attendance-permission.view']),
            Route::post('/attendance-permission/create')
                ->action(AttendancePermissionV1\AttendancePermissionCreateAction::class)
                ->name('v1/attendance-permission/create')
                ->defaults(['permission' => 'attendance-permission.create']),
            Route::put('/attendance-permission/{id:\d+}')
                ->action(AttendancePermissionV1\AttendancePermissionUpdateAction::class)
                ->name('v1/attendance-permission/update')
                ->defaults(['permission' => 'attendance-permission.update']),
            Route::delete('/attendance-permission/{id:\d+}')
                ->action(AttendancePermissionV1\AttendancePermissionDeleteAction::class)
                ->name('v1/attendance-permission/delete')
                ->defaults(['permission' => 'attendance-permission.delete']),
            Route::post('/attendance-permission/{id:\d+}/restore')
                ->action(AttendancePermissionV1\AttendancePermissionRestoreAction::class)
                ->name('v1/attendance-permission/restore')
                ->defaults(['permission' => 'attendance-permission.restore']),
            Route::put('/attendance-permission/{id:\d+}/approve')
                ->action(AttendancePermissionV1\AttendancePermissionApproveAction::class)
                ->name('v1/attendance-permission/approve')
                ->defaults(['permission' => 'attendance-permission.approve']),
            Route::put('/attendance-permission/{id:\d+}/reject')
                ->action(AttendancePermissionV1\AttendancePermissionRejectAction::class)
                ->name('v1/attendance-permission/reject')
                ->defaults(['permission' => 'attendance-permission.reject']),
            
            // Attendance Routes
            Route::get('/attendance')
                ->action(AttendanceV1\AttendanceDataAction::class)
                ->name('v1/attendance/index')
                ->defaults(['permission' => 'attendance.index']),
            Route::post('/attendance/data')
                ->action(AttendanceV1\AttendanceDataAction::class)
                ->name('v1/attendance/data')
                ->defaults(['permission' => 'attendance.data']),
            Route::get('/attendance/{id:\d+}')
                ->action(AttendanceV1\AttendanceViewAction::class)
                ->name('v1/attendance/view')
                ->defaults(['permission' => 'attendance.view']),
            Route::post('/attendance/create')
                ->action(AttendanceV1\AttendanceCreateAction::class)
                ->name('v1/attendance/create')
                ->defaults(['permission' => 'attendance.create']),
            Route::put('/attendance/{id:\d+}')
                ->action(AttendanceV1\AttendanceUpdateAction::class)
                ->name('v1/attendance/update')
                ->defaults(['permission' => 'attendance.update']),
            Route::delete('/attendance/{id:\d+}')
                ->action(AttendanceV1\AttendanceDeleteAction::class)
                ->name('v1/attendance/delete')
                ->defaults(['permission' => 'attendance.delete']),
            Route::post('/attendance/{id:\d+}/restore')
                ->action(AttendanceV1\AttendanceRestoreAction::class)
                ->name('v1/attendance/restore')
                ->defaults(['permission' => 'attendance.restore']),
            Route::post('/attendance/adjustment')
                ->action(AttendanceV1\AttendanceAdjustmentAction::class)
                ->name('v1/attendance/adjustment')
                ->defaults(['permission' => 'attendance.adjustment']),
            
            /**
             * Employee Module
             */

            // EmployeeDivision Routes
            Route::get('/employee-division')
                ->action(EmployeeDivisionV1\EmployeeDivisionDataAction::class)
                ->name('v1/employee-division/index')
                ->defaults(['permission' => 'employee-division.index']),
            Route::post('/employee-division/data')
                ->action(EmployeeDivisionV1\EmployeeDivisionDataAction::class)
                ->name('v1/employee-division/data')
                ->defaults(['permission' => 'employee-division.data']),
            Route::get('/employee-division/{id:\d+}')
                ->action(EmployeeDivisionV1\EmployeeDivisionViewAction::class)
                ->name('v1/employee-division/view')
                ->defaults(['permission' => 'employee-division.view']),
            Route::post('/employee-division/create')
                ->action(EmployeeDivisionV1\EmployeeDivisionCreateAction::class)
                ->name('v1/employee-division/create')
                ->defaults(['permission' => 'employee-division.create']),
            Route::put('/employee-division/{id:\d+}')
                ->action(EmployeeDivisionV1\EmployeeDivisionUpdateAction::class)
                ->name('v1/employee-division/update')
                ->defaults(['permission' => 'employee-division.update']),
            Route::delete('/employee-division/{id:\d+}')
                ->action(EmployeeDivisionV1\EmployeeDivisionDeleteAction::class)
                ->name('v1/employee-division/delete')
                ->defaults(['permission' => 'employee-division.delete']),
            Route::post('/employee-division/{id:\d+}/restore')
                ->action(EmployeeDivisionV1\EmployeeDivisionRestoreAction::class)
                ->name('v1/employee-division/restore')
                ->defaults(['permission' => 'employee-division.restore']),

            // EmployeePosition Routes
            Route::get('/employee-position')
                ->action(EmployeePositionV1\EmployeePositionDataAction::class)
                ->name('v1/employee-position/index')
                ->defaults(['permission' => 'employee-position.index']),
            Route::post('/employee-position/data')
                ->action(EmployeePositionV1\EmployeePositionDataAction::class)
                ->name('v1/employee-position/data')
                ->defaults(['permission' => 'employee-position.data']),
            Route::get('/employee-position/{id:\d+}')
                ->action(EmployeePositionV1\EmployeePositionViewAction::class)
                ->name('v1/employee-position/view')
                ->defaults(['permission' => 'employee-position.view']),
            Route::post('/employee-position/create')
                ->action(EmployeePositionV1\EmployeePositionCreateAction::class)
                ->name('v1/employee-position/create')
                ->defaults(['permission' => 'employee-position.create']),
            Route::put('/employee-position/{id:\d+}')
                ->action(EmployeePositionV1\EmployeePositionUpdateAction::class)
                ->name('v1/employee-position/update')
                ->defaults(['permission' => 'employee-position.update']),
            Route::delete('/employee-position/{id:\d+}')
                ->action(EmployeePositionV1\EmployeePositionDeleteAction::class)
                ->name('v1/employee-position/delete')
                ->defaults(['permission' => 'employee-position.delete']),
            Route::post('/employee-position/{id:\d+}/restore')
                ->action(EmployeePositionV1\EmployeePositionRestoreAction::class)
                ->name('v1/employee-position/restore')
                ->defaults(['permission' => 'employee-position.restore']),

            // EmployeeType Routes
            Route::get('/employee-type')
                ->action(EmployeeTypeV1\EmployeeTypeDataAction::class)
                ->name('v1/employee-type/index')
                ->defaults(['permission' => 'employee-type.index']),
            Route::post('/employee-type/data')
                ->action(EmployeeTypeV1\EmployeeTypeDataAction::class)
                ->name('v1/employee-type/data')
                ->defaults(['permission' => 'employee-type.data']),
            Route::get('/employee-type/{id:\d+}')
                ->action(EmployeeTypeV1\EmployeeTypeViewAction::class)
                ->name('v1/employee-type/view')
                ->defaults(['permission' => 'employee-type.view']),
            Route::post('/employee-type/create')
                ->action(EmployeeTypeV1\EmployeeTypeCreateAction::class)
                ->name('v1/employee-type/create')
                ->defaults(['permission' => 'employee-type.create']),
            Route::put('/employee-type/{id:\d+}')
                ->action(EmployeeTypeV1\EmployeeTypeUpdateAction::class)
                ->name('v1/employee-type/update')
                ->defaults(['permission' => 'employee-type.update']),
            Route::delete('/employee-type/{id:\d+}')
                ->action(EmployeeTypeV1\EmployeeTypeDeleteAction::class)
                ->name('v1/employee-type/delete')
                ->defaults(['permission' => 'employee-type.delete']),
            Route::post('/employee-type/{id:\d+}/restore')
                ->action(EmployeeTypeV1\EmployeeTypeRestoreAction::class)
                ->name('v1/employee-type/restore')
                ->defaults(['permission' => 'employee-type.restore']),

            // Employee Routes
            Route::get('/employee')
                ->action(EmployeeV1\EmployeeDataAction::class)
                ->name('v1/employee/index')
                ->defaults(['permission' => 'employee.index']),
            Route::post('/employee/data')
                ->action(EmployeeV1\EmployeeDataAction::class)
                ->name('v1/employee/data')
                ->defaults(['permission' => 'employee.data']),
            Route::get('/employee/{id:\d+}')
                ->action(EmployeeV1\EmployeeViewAction::class)
                ->name('v1/employee/view')
                ->defaults(['permission' => 'employee.view']),
            Route::post('/employee/create')
                ->action(EmployeeV1\EmployeeCreateAction::class)
                ->name('v1/employee/create')
                ->defaults(['permission' => 'employee.create']),
            Route::put('/employee/{id:\d+}')
                ->action(EmployeeV1\EmployeeUpdateAction::class)
                ->name('v1/employee/update')
                ->defaults(['permission' => 'employee.update']),
            Route::delete('/employee/{id:\d+}')
                ->action(EmployeeV1\EmployeeDeleteAction::class)
                ->name('v1/employee/delete')
                ->defaults(['permission' => 'employee.delete']),
            Route::post('/employee/{id:\d+}/restore')
                ->action(EmployeeV1\EmployeeRestoreAction::class)
                ->name('v1/employee/restore')
                ->defaults(['permission' => 'employee.restore']),

            /**
             * General Affair Module
             */

            // GeneralAffairProduct Routes
            Route::get('/general-affair-product')
                ->action(GeneralAffairProductV1\GeneralAffairProductDataAction::class)
                ->name('v1/general-affair-product/index')
                ->defaults(['permission' => 'general-affair-product.index']),
            Route::post('/general-affair-product/data')
                ->action(GeneralAffairProductV1\GeneralAffairProductDataAction::class)
                ->name('v1/general-affair-product/data')
                ->defaults(['permission' => 'general-affair-product.data']),
            Route::get('/general-affair-product/{id:\d+}')
                ->action(GeneralAffairProductV1\GeneralAffairProductViewAction::class)
                ->name('v1/general-affair-product/view')
                ->defaults(['permission' => 'general-affair-product.view']),
            Route::post('/general-affair-product/create')
                ->action(GeneralAffairProductV1\GeneralAffairProductCreateAction::class)
                ->name('v1/general-affair-product/create')
                ->defaults(['permission' => 'general-affair-product.create']),
            Route::put('/general-affair-product/{id:\d+}')
                ->action(GeneralAffairProductV1\GeneralAffairProductUpdateAction::class)
                ->name('v1/general-affair-product/update')
                ->defaults(['permission' => 'general-affair-product.update']),
            Route::delete('/general-affair-product/{id:\d+}')
                ->action(GeneralAffairProductV1\GeneralAffairProductDeleteAction::class)
                ->name('v1/general-affair-product/delete')
                ->defaults(['permission' => 'general-affair-product.delete']),
            Route::post('/general-affair-product/{id:\d+}/restore')
                ->action(GeneralAffairProductV1\GeneralAffairProductRestoreAction::class)
                ->name('v1/general-affair-product/restore')
                ->defaults(['permission' => 'general-affair-product.restore']),

            // GeneralAffairCategory Routes
            Route::get('/general-affair-category')
                ->action(GeneralAffairCategoryV1\GeneralAffairCategoryDataAction::class)
                ->name('v1/general-affair-category/index')
                ->defaults(['permission' => 'general-affair-category.index']),
            Route::post('/general-affair-category/data')
                ->action(GeneralAffairCategoryV1\GeneralAffairCategoryDataAction::class)
                ->name('v1/general-affair-category/data')
                ->defaults(['permission' => 'general-affair-category.data']),
            Route::get('/general-affair-category/{id:\d+}')
                ->action(GeneralAffairCategoryV1\GeneralAffairCategoryViewAction::class)
                ->name('v1/general-affair-category/view')
                ->defaults(['permission' => 'general-affair-category.view']),
            Route::post('/general-affair-category/create')
                ->action(GeneralAffairCategoryV1\GeneralAffairCategoryCreateAction::class)
                ->name('v1/general-affair-category/create')
                ->defaults(['permission' => 'general-affair-category.create']),
            Route::put('/general-affair-category/{id:\d+}')
                ->action(GeneralAffairCategoryV1\GeneralAffairCategoryUpdateAction::class)
                ->name('v1/general-affair-category/update')
                ->defaults(['permission' => 'general-affair-category.update']),
            Route::delete('/general-affair-category/{id:\d+}')
                ->action(GeneralAffairCategoryV1\GeneralAffairCategoryDeleteAction::class)
                ->name('v1/general-affair-category/delete')
                ->defaults(['permission' => 'general-affair-category.delete']),
            Route::post('/general-affair-category/{id:\d+}/restore')
                ->action(GeneralAffairCategoryV1\GeneralAffairCategoryRestoreAction::class)
                ->name('v1/general-affair-category/restore')
                ->defaults(['permission' => 'general-affair-category.restore']),

            // GeneralAffairLocation Routes
            Route::get('/general-affair-location')
                ->action(GeneralAffairLocationV1\GeneralAffairLocationDataAction::class)
                ->name('v1/general-affair-location/index')
                ->defaults(['permission' => 'general-affair-location.index']),
            Route::post('/general-affair-location/data')
                ->action(GeneralAffairLocationV1\GeneralAffairLocationDataAction::class)
                ->name('v1/general-affair-location/data')
                ->defaults(['permission' => 'general-affair-location.data']),
            Route::get('/general-affair-location/{id:\d+}')
                ->action(GeneralAffairLocationV1\GeneralAffairLocationViewAction::class)
                ->name('v1/general-affair-location/view')
                ->defaults(['permission' => 'general-affair-location.view']),
            Route::post('/general-affair-location/create')
                ->action(GeneralAffairLocationV1\GeneralAffairLocationCreateAction::class)
                ->name('v1/general-affair-location/create')
                ->defaults(['permission' => 'general-affair-location.create']),
            Route::put('/general-affair-location/{id:\d+}')
                ->action(GeneralAffairLocationV1\GeneralAffairLocationUpdateAction::class)
                ->name('v1/general-affair-location/update')
                ->defaults(['permission' => 'general-affair-location.update']),
            Route::delete('/general-affair-location/{id:\d+}')
                ->action(GeneralAffairLocationV1\GeneralAffairLocationDeleteAction::class)
                ->name('v1/general-affair-location/delete')
                ->defaults(['permission' => 'general-affair-location.delete']),
            Route::post('/general-affair-location/{id:\d+}/restore')
                ->action(GeneralAffairLocationV1\GeneralAffairLocationRestoreAction::class)
                ->name('v1/general-affair-location/restore')
                ->defaults(['permission' => 'general-affair-location.restore']),

            // GeneralAffair Routes
            Route::get('/general-affair')
                ->action(GeneralAffairV1\GeneralAffairDataAction::class)
                ->name('v1/general-affair/index')
                ->defaults(['permission' => 'general-affair.index']),
            Route::post('/general-affair/data')
                ->action(GeneralAffairV1\GeneralAffairDataAction::class)
                ->name('v1/general-affair/data')
                ->defaults(['permission' => 'general-affair.data']),
            Route::get('/general-affair/{id:\d+}')
                ->action(GeneralAffairV1\GeneralAffairViewAction::class)
                ->name('v1/general-affair/view')
                ->defaults(['permission' => 'general-affair.view']),
            Route::post('/general-affair/create')
                ->action(GeneralAffairV1\GeneralAffairCreateAction::class)
                ->name('v1/general-affair/create')
                ->defaults(['permission' => 'general-affair.create']),
            Route::put('/general-affair/{id:\d+}')
                ->action(GeneralAffairV1\GeneralAffairUpdateAction::class)
                ->name('v1/general-affair/update')
                ->defaults(['permission' => 'general-affair.update']),
            Route::delete('/general-affair/{id:\d+}')
                ->action(GeneralAffairV1\GeneralAffairDeleteAction::class)
                ->name('v1/general-affair/delete')
                ->defaults(['permission' => 'general-affair.delete']),
            Route::post('/general-affair/{id:\d+}/restore')
                ->action(GeneralAffairV1\GeneralAffairRestoreAction::class)
                ->name('v1/general-affair/restore')
                ->defaults(['permission' => 'general-affair.restore']),

            /**
             * Attenndace Reporting System
             */
            Route::post('/attendance/{id:\d+}/show')
                ->action(AttendanceV1\AttendanceShowAction::class)
                ->name('v1/attendance/show')
                ->defaults(['permission' => 'attendance.show']),
            Route::post('/attendance/{id:\d+}/report')
                ->action(AttendanceV1\AttendanceReportAction::class)
                ->name('v1/attendance/report')
                ->defaults(['permission' => 'attendance.report']),

            // Branch Routes
            Route::get('/branch')
                ->action(BranchV1\BranchDataAction::class)
                ->name('v1/branch/index')
                ->defaults(['permission' => 'branch.index']),
            Route::post('/branch/data')
                ->action(BranchV1\BranchDataAction::class)
                ->name('v1/branch/data')
                ->defaults(['permission' => 'branch.data']),
            Route::get('/branch/{id:\d+}')
                ->action(BranchV1\BranchViewAction::class)
                ->name('v1/branch/view')
                ->defaults(['permission' => 'branch.view']),
            Route::post('/branch/create')
                ->action(BranchV1\BranchCreateAction::class)
                ->name('v1/branch/create')
                ->defaults(['permission' => 'branch.create']),
            Route::put('/branch/{id:\d+}')
                ->action(BranchV1\BranchUpdateAction::class)
                ->name('v1/branch/update')
                ->defaults(['permission' => 'branch.update']),
            Route::delete('/branch/{id:\d+}')
                ->action(BranchV1\BranchDeleteAction::class)
                ->name('v1/branch/delete')
                ->defaults(['permission' => 'branch.delete']),
            Route::post('/branch/{id:\d+}/restore')
                ->action(BranchV1\BranchRestoreAction::class)
                ->name('v1/branch/restore')
                ->defaults(['permission' => 'branch.restore']),

            // EmployeeLeave Routes
            Route::get('/employee-leave')
                ->action(EmployeeLeaveV1\EmployeeLeaveDataAction::class)
                ->name('v1/employee-leave/index')
                ->defaults(['permission' => 'employee-leave.index']),
            Route::post('/employee-leave/data')
                ->action(EmployeeLeaveV1\EmployeeLeaveDataAction::class)
                ->name('v1/employee-leave/data')
                ->defaults(['permission' => 'employee-leave.data']),
            Route::get('/employee-leave/{id:\d+}')
                ->action(EmployeeLeaveV1\EmployeeLeaveViewAction::class)
                ->name('v1/employee-leave/view')
                ->defaults(['permission' => 'employee-leave.view']),
            Route::post('/employee-leave/create')
                ->action(EmployeeLeaveV1\EmployeeLeaveCreateAction::class)
                ->name('v1/employee-leave/create')
                ->defaults(['permission' => 'employee-leave.create']),
            Route::put('/employee-leave/{id:\d+}')
                ->action(EmployeeLeaveV1\EmployeeLeaveUpdateAction::class)
                ->name('v1/employee-leave/update')
                ->defaults(['permission' => 'employee-leave.update']),
            Route::delete('/employee-leave/{id:\d+}')
                ->action(EmployeeLeaveV1\EmployeeLeaveDeleteAction::class)
                ->name('v1/employee-leave/delete')
                ->defaults(['permission' => 'employee-leave.delete']),
            Route::post('/employee-leave/{id:\d+}/restore')
                ->action(EmployeeLeaveV1\EmployeeLeaveRestoreAction::class)
                ->name('v1/employee-leave/restore')
                ->defaults(['permission' => 'employee-leave.restore']),

            // EmployeeGrade Routes
            Route::get('/employee-grade')
                ->action(EmployeeGradeV1\EmployeeGradeDataAction::class)
                ->name('v1/employee-grade/index')
                ->defaults(['permission' => 'employee-grade.index']),
            Route::post('/employee-grade/data')
                ->action(EmployeeGradeV1\EmployeeGradeDataAction::class)
                ->name('v1/employee-grade/data')
                ->defaults(['permission' => 'employee-grade.data']),
            Route::get('/employee-grade/{id:\d+}')
                ->action(EmployeeGradeV1\EmployeeGradeViewAction::class)
                ->name('v1/employee-grade/view')
                ->defaults(['permission' => 'employee-grade.view']),
            Route::post('/employee-grade/create')
                ->action(EmployeeGradeV1\EmployeeGradeCreateAction::class)
                ->name('v1/employee-grade/create')
                ->defaults(['permission' => 'employee-grade.create']),
            Route::put('/employee-grade/{id:\d+}')
                ->action(EmployeeGradeV1\EmployeeGradeUpdateAction::class)
                ->name('v1/employee-grade/update')
                ->defaults(['permission' => 'employee-grade.update']),
            Route::delete('/employee-grade/{id:\d+}')
                ->action(EmployeeGradeV1\EmployeeGradeDeleteAction::class)
                ->name('v1/employee-grade/delete')
                ->defaults(['permission' => 'employee-grade.delete']),
            Route::post('/employee-grade/{id:\d+}/restore')
                ->action(EmployeeGradeV1\EmployeeGradeRestoreAction::class)
                ->name('v1/employee-grade/restore')
                ->defaults(['permission' => 'employee-grade.restore']),

            // AttendancePolicies Routes
            Route::get('/attendance-policies')
                ->action(AttendancePoliciesV1\AttendancePoliciesDataAction::class)
                ->name('v1/attendance-policies/index')
                ->defaults(['permission' => 'attendance-policies.index']),
            Route::post('/attendance-policies/data')
                ->action(AttendancePoliciesV1\AttendancePoliciesDataAction::class)
                ->name('v1/attendance-policies/data')
                ->defaults(['permission' => 'attendance-policies.data']),
            Route::get('/attendance-policies/{id:\d+}')
                ->action(AttendancePoliciesV1\AttendancePoliciesViewAction::class)
                ->name('v1/attendance-policies/view')
                ->defaults(['permission' => 'attendance-policies.view']),
            Route::post('/attendance-policies/create')
                ->action(AttendancePoliciesV1\AttendancePoliciesCreateAction::class)
                ->name('v1/attendance-policies/create')
                ->defaults(['permission' => 'attendance-policies.create']),
            Route::put('/attendance-policies/{id:\d+}')
                ->action(AttendancePoliciesV1\AttendancePoliciesUpdateAction::class)
                ->name('v1/attendance-policies/update')
                ->defaults(['permission' => 'attendance-policies.update']),
            Route::delete('/attendance-policies/{id:\d+}')
                ->action(AttendancePoliciesV1\AttendancePoliciesDeleteAction::class)
                ->name('v1/attendance-policies/delete')
                ->defaults(['permission' => 'attendance-policies.delete']),
            Route::post('/attendance-policies/{id:\d+}/restore')
                ->action(AttendancePoliciesV1\AttendancePoliciesRestoreAction::class)
                ->name('v1/attendance-policies/restore')
                ->defaults(['permission' => 'attendance-policies.restore']),

            // AttendanceCalendar Routes
            Route::get('/attendance-calendar')
                ->action(AttendanceCalendarV1\AttendanceCalendarDataAction::class)
                ->name('v1/attendance-calendar/index')
                ->defaults(['permission' => 'attendance-calendar.index']),
            Route::post('/attendance-calendar/data')
                ->action(AttendanceCalendarV1\AttendanceCalendarDataAction::class)
                ->name('v1/attendance-calendar/data')
                ->defaults(['permission' => 'attendance-calendar.data']),
            Route::get('/attendance-calendar/{id:\d+}')
                ->action(AttendanceCalendarV1\AttendanceCalendarViewAction::class)
                ->name('v1/attendance-calendar/view')
                ->defaults(['permission' => 'attendance-calendar.view']),
            Route::post('/attendance-calendar/create')
                ->action(AttendanceCalendarV1\AttendanceCalendarCreateAction::class)
                ->name('v1/attendance-calendar/create')
                ->defaults(['permission' => 'attendance-calendar.create']),
            Route::put('/attendance-calendar/{id:\d+}')
                ->action(AttendanceCalendarV1\AttendanceCalendarUpdateAction::class)
                ->name('v1/attendance-calendar/update')
                ->defaults(['permission' => 'attendance-calendar.update']),
            Route::delete('/attendance-calendar/{id:\d+}')
                ->action(AttendanceCalendarV1\AttendanceCalendarDeleteAction::class)
                ->name('v1/attendance-calendar/delete')
                ->defaults(['permission' => 'attendance-calendar.delete']),
            Route::post('/attendance-calendar/{id:\d+}/restore')
                ->action(AttendanceCalendarV1\AttendanceCalendarRestoreAction::class)
                ->name('v1/attendance-calendar/restore')
                ->defaults(['permission' => 'attendance-calendar.restore']),

            // AttendanceShift Routes
            Route::get('/attendance-shift')
                ->action(AttendanceShiftV1\AttendanceShiftDataAction::class)
                ->name('v1/attendance-shift/index')
                ->defaults(['permission' => 'attendance-shift.index']),
            Route::post('/attendance-shift/data')
                ->action(AttendanceShiftV1\AttendanceShiftDataAction::class)
                ->name('v1/attendance-shift/data')
                ->defaults(['permission' => 'attendance-shift.data']),
            Route::get('/attendance-shift/{id:\d+}')
                ->action(AttendanceShiftV1\AttendanceShiftViewAction::class)
                ->name('v1/attendance-shift/view')
                ->defaults(['permission' => 'attendance-shift.view']),
            Route::post('/attendance-shift/create')
                ->action(AttendanceShiftV1\AttendanceShiftCreateAction::class)
                ->name('v1/attendance-shift/create')
                ->defaults(['permission' => 'attendance-shift.create']),
            Route::put('/attendance-shift/{id:\d+}')
                ->action(AttendanceShiftV1\AttendanceShiftUpdateAction::class)
                ->name('v1/attendance-shift/update')
                ->defaults(['permission' => 'attendance-shift.update']),
            Route::delete('/attendance-shift/{id:\d+}')
                ->action(AttendanceShiftV1\AttendanceShiftDeleteAction::class)
                ->name('v1/attendance-shift/delete')
                ->defaults(['permission' => 'attendance-shift.delete']),
            Route::post('/attendance-shift/{id:\d+}/restore')
                ->action(AttendanceShiftV1\AttendanceShiftRestoreAction::class)
                ->name('v1/attendance-shift/restore')
                ->defaults(['permission' => 'attendance-shift.restore']),

            // AttendancePeriod Routes
            Route::get('/attendance-period')
                ->action(AttendancePeriodV1\AttendancePeriodDataAction::class)
                ->name('v1/attendance-period/index')
                ->defaults(['permission' => 'attendance-period.index']),
            Route::post('/attendance-period/data')
                ->action(AttendancePeriodV1\AttendancePeriodDataAction::class)
                ->name('v1/attendance-period/data')
                ->defaults(['permission' => 'attendance-period.data']),
            Route::get('/attendance-period/{id:\d+}')
                ->action(AttendancePeriodV1\AttendancePeriodViewAction::class)
                ->name('v1/attendance-period/view')
                ->defaults(['permission' => 'attendance-period.view']),
            Route::post('/attendance-period/create')
                ->action(AttendancePeriodV1\AttendancePeriodCreateAction::class)
                ->name('v1/attendance-period/create')
                ->defaults(['permission' => 'attendance-period.create']),
            Route::put('/attendance-period/{id:\d+}')
                ->action(AttendancePeriodV1\AttendancePeriodUpdateAction::class)
                ->name('v1/attendance-period/update')
                ->defaults(['permission' => 'attendance-period.update']),
            Route::delete('/attendance-period/{id:\d+}')
                ->action(AttendancePeriodV1\AttendancePeriodDeleteAction::class)
                ->name('v1/attendance-period/delete')
                ->defaults(['permission' => 'attendance-period.delete']),
            Route::post('/attendance-period/{id:\d+}/restore')
                ->action(AttendancePeriodV1\AttendancePeriodRestoreAction::class)
                ->name('v1/attendance-period/restore')
                ->defaults(['permission' => 'attendance-period.restore']),

            // AttendanceReport Routes
            Route::get('/attendance-report')
                ->action(AttendanceReportV1\AttendanceReportDataAction::class)
                ->name('v1/attendance-report/index')
                ->defaults(['permission' => 'attendance-report.index']),
            Route::post('/attendance-report/data')
                ->action(AttendanceReportV1\AttendanceReportDataAction::class)
                ->name('v1/attendance-report/data')
                ->defaults(['permission' => 'attendance-report.data']),
            Route::get('/attendance-report/{id:\d+}')
                ->action(AttendanceReportV1\AttendanceReportViewAction::class)
                ->name('v1/attendance-report/view')
                ->defaults(['permission' => 'attendance-report.view']),
            Route::post('/attendance-report/create')
                ->action(AttendanceReportV1\AttendanceReportCreateAction::class)
                ->name('v1/attendance-report/create')
                ->defaults(['permission' => 'attendance-report.create']),
            Route::put('/attendance-report/{id:\d+}')
                ->action(AttendanceReportV1\AttendanceReportUpdateAction::class)
                ->name('v1/attendance-report/update')
                ->defaults(['permission' => 'attendance-report.update']),
            Route::delete('/attendance-report/{id:\d+}')
                ->action(AttendanceReportV1\AttendanceReportDeleteAction::class)
                ->name('v1/attendance-report/delete')
                ->defaults(['permission' => 'attendance-report.delete']),
            Route::post('/attendance-report/{id:\d+}/restore')
                ->action(AttendanceReportV1\AttendanceReportRestoreAction::class)
                ->name('v1/attendance-report/restore')
                ->defaults(['permission' => 'attendance-report.restore']),

            // PayrollDeductionType Routes
            Route::get('/payroll-deduction-type')
                ->action(PayrollDeductionTypeV1\PayrollDeductionTypeDataAction::class)
                ->name('v1/payroll-deduction-type/index')
                ->defaults(['permission' => 'payroll-deduction-type.index']),
            Route::post('/payroll-deduction-type/data')
                ->action(PayrollDeductionTypeV1\PayrollDeductionTypeDataAction::class)
                ->name('v1/payroll-deduction-type/data')
                ->defaults(['permission' => 'payroll-deduction-type.data']),
            Route::get('/payroll-deduction-type/{id:\d+}')
                ->action(PayrollDeductionTypeV1\PayrollDeductionTypeViewAction::class)
                ->name('v1/payroll-deduction-type/view')
                ->defaults(['permission' => 'payroll-deduction-type.view']),
            Route::post('/payroll-deduction-type/create')
                ->action(PayrollDeductionTypeV1\PayrollDeductionTypeCreateAction::class)
                ->name('v1/payroll-deduction-type/create')
                ->defaults(['permission' => 'payroll-deduction-type.create']),
            Route::put('/payroll-deduction-type/{id:\d+}')
                ->action(PayrollDeductionTypeV1\PayrollDeductionTypeUpdateAction::class)
                ->name('v1/payroll-deduction-type/update')
                ->defaults(['permission' => 'payroll-deduction-type.update']),
            Route::delete('/payroll-deduction-type/{id:\d+}')
                ->action(PayrollDeductionTypeV1\PayrollDeductionTypeDeleteAction::class)
                ->name('v1/payroll-deduction-type/delete')
                ->defaults(['permission' => 'payroll-deduction-type.delete']),
            Route::post('/payroll-deduction-type/{id:\d+}/restore')
                ->action(PayrollDeductionTypeV1\PayrollDeductionTypeRestoreAction::class)
                ->name('v1/payroll-deduction-type/restore')
                ->defaults(['permission' => 'payroll-deduction-type.restore']),

            // EmployeeDebt Routes
            Route::get('/employee-debt')
                ->action(EmployeeDebtV1\EmployeeDebtDataAction::class)
                ->name('v1/employee-debt/index')
                ->defaults(['permission' => 'employee-debt.index']),
            Route::post('/employee-debt/data')
                ->action(EmployeeDebtV1\EmployeeDebtDataAction::class)
                ->name('v1/employee-debt/data')
                ->defaults(['permission' => 'employee-debt.data']),
            Route::get('/employee-debt/{id:\d+}')
                ->action(EmployeeDebtV1\EmployeeDebtViewAction::class)
                ->name('v1/employee-debt/view')
                ->defaults(['permission' => 'employee-debt.view']),
            Route::post('/employee-debt/create')
                ->action(EmployeeDebtV1\EmployeeDebtCreateAction::class)
                ->name('v1/employee-debt/create')
                ->defaults(['permission' => 'employee-debt.create']),
            Route::put('/employee-debt/{id:\d+}')
                ->action(EmployeeDebtV1\EmployeeDebtUpdateAction::class)
                ->name('v1/employee-debt/update')
                ->defaults(['permission' => 'employee-debt.update']),
            Route::delete('/employee-debt/{id:\d+}')
                ->action(EmployeeDebtV1\EmployeeDebtDeleteAction::class)
                ->name('v1/employee-debt/delete')
                ->defaults(['permission' => 'employee-debt.delete']),
            Route::post('/employee-debt/{id:\d+}/restore')
                ->action(EmployeeDebtV1\EmployeeDebtRestoreAction::class)
                ->name('v1/employee-debt/restore')
                ->defaults(['permission' => 'employee-debt.restore']),

            // PayrollDeduction Routes
            Route::get('/payroll-deduction')
                ->action(PayrollDeductionV1\PayrollDeductionDataAction::class)
                ->name('v1/payroll-deduction/index')
                ->defaults(['permission' => 'payroll-deduction.index']),
            Route::post('/payroll-deduction/data')
                ->action(PayrollDeductionV1\PayrollDeductionDataAction::class)
                ->name('v1/payroll-deduction/data')
                ->defaults(['permission' => 'payroll-deduction.data']),
            Route::get('/payroll-deduction/{id:\d+}')
                ->action(PayrollDeductionV1\PayrollDeductionViewAction::class)
                ->name('v1/payroll-deduction/view')
                ->defaults(['permission' => 'payroll-deduction.view']),
            Route::post('/payroll-deduction/create')
                ->action(PayrollDeductionV1\PayrollDeductionCreateAction::class)
                ->name('v1/payroll-deduction/create')
                ->defaults(['permission' => 'payroll-deduction.create']),
            Route::put('/payroll-deduction/{id:\d+}')
                ->action(PayrollDeductionV1\PayrollDeductionUpdateAction::class)
                ->name('v1/payroll-deduction/update')
                ->defaults(['permission' => 'payroll-deduction.update']),
            Route::delete('/payroll-deduction/{id:\d+}')
                ->action(PayrollDeductionV1\PayrollDeductionDeleteAction::class)
                ->name('v1/payroll-deduction/delete')
                ->defaults(['permission' => 'payroll-deduction.delete']),
            Route::post('/payroll-deduction/{id:\d+}/restore')
                ->action(PayrollDeductionV1\PayrollDeductionRestoreAction::class)
                ->name('v1/payroll-deduction/restore')
                ->defaults(['permission' => 'payroll-deduction.restore']),

            // EmployeeDebtInstallment Routes
            Route::get('/employee-debt-installment')
                ->action(EmployeeDebtInstallmentV1\EmployeeDebtInstallmentDataAction::class)
                ->name('v1/employee-debt-installment/index')
                ->defaults(['permission' => 'employee-debt-installment.index']),
            Route::post('/employee-debt-installment/data')
                ->action(EmployeeDebtInstallmentV1\EmployeeDebtInstallmentDataAction::class)
                ->name('v1/employee-debt-installment/data')
                ->defaults(['permission' => 'employee-debt-installment.data']),
            Route::get('/employee-debt-installment/{id:\d+}')
                ->action(EmployeeDebtInstallmentV1\EmployeeDebtInstallmentViewAction::class)
                ->name('v1/employee-debt-installment/view')
                ->defaults(['permission' => 'employee-debt-installment.view']),
            Route::post('/employee-debt-installment/create')
                ->action(EmployeeDebtInstallmentV1\EmployeeDebtInstallmentCreateAction::class)
                ->name('v1/employee-debt-installment/create')
                ->defaults(['permission' => 'employee-debt-installment.create']),
            Route::put('/employee-debt-installment/{id:\d+}')
                ->action(EmployeeDebtInstallmentV1\EmployeeDebtInstallmentUpdateAction::class)
                ->name('v1/employee-debt-installment/update')
                ->defaults(['permission' => 'employee-debt-installment.update']),
            Route::delete('/employee-debt-installment/{id:\d+}')
                ->action(EmployeeDebtInstallmentV1\EmployeeDebtInstallmentDeleteAction::class)
                ->name('v1/employee-debt-installment/delete')
                ->defaults(['permission' => 'employee-debt-installment.delete']),
            Route::post('/employee-debt-installment/{id:\d+}/restore')
                ->action(EmployeeDebtInstallmentV1\EmployeeDebtInstallmentRestoreAction::class)
                ->name('v1/employee-debt-installment/restore')
                ->defaults(['permission' => 'employee-debt-installment.restore']),

            // EmployeeDebtTransaction Routes
            Route::get('/employee-debt-transaction')
                ->action(EmployeeDebtTransactionV1\EmployeeDebtTransactionDataAction::class)
                ->name('v1/employee-debt-transaction/index')
                ->defaults(['permission' => 'employee-debt-transaction.index']),
            Route::post('/employee-debt-transaction/data')
                ->action(EmployeeDebtTransactionV1\EmployeeDebtTransactionDataAction::class)
                ->name('v1/employee-debt-transaction/data')
                ->defaults(['permission' => 'employee-debt-transaction.data']),
            Route::get('/employee-debt-transaction/{id:\d+}')
                ->action(EmployeeDebtTransactionV1\EmployeeDebtTransactionViewAction::class)
                ->name('v1/employee-debt-transaction/view')
                ->defaults(['permission' => 'employee-debt-transaction.view']),
            Route::post('/employee-debt-transaction/create')
                ->action(EmployeeDebtTransactionV1\EmployeeDebtTransactionCreateAction::class)
                ->name('v1/employee-debt-transaction/create')
                ->defaults(['permission' => 'employee-debt-transaction.create']),
            Route::put('/employee-debt-transaction/{id:\d+}')
                ->action(EmployeeDebtTransactionV1\EmployeeDebtTransactionUpdateAction::class)
                ->name('v1/employee-debt-transaction/update')
                ->defaults(['permission' => 'employee-debt-transaction.update']),
            Route::delete('/employee-debt-transaction/{id:\d+}')
                ->action(EmployeeDebtTransactionV1\EmployeeDebtTransactionDeleteAction::class)
                ->name('v1/employee-debt-transaction/delete')
                ->defaults(['permission' => 'employee-debt-transaction.delete']),
            Route::post('/employee-debt-transaction/{id:\d+}/restore')
                ->action(EmployeeDebtTransactionV1\EmployeeDebtTransactionRestoreAction::class)
                ->name('v1/employee-debt-transaction/restore')
                ->defaults(['permission' => 'employee-debt-transaction.restore']),



        ),
];