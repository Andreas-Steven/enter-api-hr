<?php

declare(strict_types=1);

// Domain Layer
use App\Api;

// Api Layer
use App\Api\V1\Example\Action as ExampleV1;
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
use App\Api\V1\WorkTime\Action as WorkTimeV1;
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

            // WorkTime Routes
            Route::get('/work-time')
                ->action(WorkTimeV1\WorkTimeDataAction::class)
                ->name('v1/work-time/index')
                ->defaults(['permission' => 'work-time.index']),
            Route::post('/work-time/data')
                ->action(WorkTimeV1\WorkTimeDataAction::class)
                ->name('v1/work-time/data')
                ->defaults(['permission' => 'work-time.data']),
            Route::get('/work-time/{id:\d+}')
                ->action(WorkTimeV1\WorkTimeViewAction::class)
                ->name('v1/work-time/view')
                ->defaults(['permission' => 'work-time.view']),
            Route::post('/work-time/create')
                ->action(WorkTimeV1\WorkTimeCreateAction::class)
                ->name('v1/work-time/create')
                ->defaults(['permission' => 'work-time.create']),
            Route::put('/work-time/{id:\d+}')
                ->action(WorkTimeV1\WorkTimeUpdateAction::class)
                ->name('v1/work-time/update')
                ->defaults(['permission' => 'work-time.update']),
            Route::delete('/work-time/{id:\d+}')
                ->action(WorkTimeV1\WorkTimeDeleteAction::class)
                ->name('v1/work-time/delete')
                ->defaults(['permission' => 'work-time.delete']),
            Route::post('/work-time/{id:\d+}/restore')
                ->action(WorkTimeV1\WorkTimeRestoreAction::class)
                ->name('v1/work-time/restore')
                ->defaults(['permission' => 'work-time.restore']),

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
        ),
];