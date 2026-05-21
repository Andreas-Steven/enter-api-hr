<?php

declare(strict_types=1);

// Infrastructure Layer
use App\Infrastructure\Security\Actor;

/** @var array $params */
$params  = require __DIR__ . '/params.php';
$appCode = $params['app/config']['code'] ?? 'default';

$isKasir      = static fn (Actor $actor): bool => $actor->hasRole($appCode, 'kasir');
$isSpv        = static fn (Actor $actor): bool => $actor->hasRole($appCode, 'spv');
$isAdmin      = static fn (Actor $actor): bool => $actor->isAdmin($appCode);
$isSuperAdmin = static fn (Actor $actor): bool => $actor->isSuperAdmin($appCode);

return [
    'example.index'  => static fn (Actor $actor): bool => true,
    'example.data'   => [
        $isSuperAdmin,
        $isKasir,
    ],
    'example.view'   => $isKasir,
    'example.create' => $isKasir,
    'example.update' => $isKasir,
    'example.delete' => $isKasir,
    'example.restore' => $isSuperAdmin,

    // AttendanceDevice Access Rules
    'attendance-device.index'  => static fn (Actor $actor): bool => true,
    'attendance-device.data'   => [
        $isSuperAdmin,
        $isKasir,
    ],
    'attendance-device.view' => $isKasir,
    'attendance-device.create' => $isKasir,
    'attendance-device.update' => $isKasir,
    'attendance-device.delete' => $isKasir,
    'attendance-device.restore' => $isKasir,

    // WorkTime Access Rules
    'work-time.index' => static fn (Actor $actor): bool => true,
    'work-time.data' => [
        $isSuperAdmin,
        $isKasir,
    ],
    'work-time.view' => $isKasir,
    'work-time.create' => $isKasir,
    'work-time.update' => $isKasir,
    'work-time.delete' => $isKasir,
    'work-time.restore' => $isSuperAdmin,

    // AttendancePermission Access Rules
    'attendance-permission.index' => static fn (Actor $actor): bool => true,
    'attendance-permission.data' => [
        $isSuperAdmin,
        $isKasir,
    ],
    'attendance-permission.view' => $isKasir,
    'attendance-permission.create' => $isKasir,
    'attendance-permission.update' => $isKasir,
    'attendance-permission.approve' => $isSuperAdmin,
    'attendance-permission.reject' => $isSuperAdmin,
    'attendance-permission.delete' => $isKasir,
    'attendance-permission.restore' => $isSuperAdmin,

    // EmployeeDivision Access Rules
    'employee-division.index' => static fn (Actor $actor): bool => true,
    'employee-division.data' => [
        $isSuperAdmin,
        $isKasir,
    ],
    'employee-division.view' => $isKasir,
    'employee-division.create' => $isKasir,
    'employee-division.update' => $isKasir,
    'employee-division.delete' => $isKasir,
    'employee-division.restore' => $isSuperAdmin,

    // EmployeePosition Access Rules
    'employee-position.index' => static fn (Actor $actor): bool => true,
    'employee-position.data' => [
        $isSuperAdmin,
        $isKasir,
    ],
    'employee-position.view' => $isKasir,
    'employee-position.create' => $isKasir,
    'employee-position.update' => $isKasir,
    'employee-position.delete' => $isKasir,
    'employee-position.restore' => $isSuperAdmin,

    // EmployeeType Access Rules
    'employee-type.index' => static fn (Actor $actor): bool => true,
    'employee-type.data' => [
        $isSuperAdmin,
        $isKasir,
    ],
    'employee-type.view' => $isKasir,
    'employee-type.create' => $isKasir,
    'employee-type.update' => $isKasir,
    'employee-type.delete' => $isKasir,
    'employee-type.restore' => $isSuperAdmin,

    // Employee Access Rules
    'employee.index' => static fn (Actor $actor): bool => true,
    'employee.data' => [
        $isSuperAdmin,
        $isKasir,
    ],
    'employee.view' => $isKasir,
    'employee.create' => $isKasir,
    'employee.update' => $isKasir,
    'employee.delete' => $isKasir,
    'employee.restore' => $isSuperAdmin,

    // Attendance Access Rules
    'attendance.index' => static fn (Actor $actor): bool => true,
    'attendance.data' => [
        $isSuperAdmin,
        $isKasir,
    ],
    'attendance.view' => $isKasir,
    'attendance.create' => $isKasir,
    'attendance.update' => $isKasir,
    'attendance.delete' => $isKasir,
    'attendance.restore' => $isSuperAdmin,
    'attendance.adjustment' => $isKasir,

    // GeneralAffairProduct Access Rules
    'general-affair-product.index' => static fn (Actor $actor): bool => true,
    'general-affair-product.data' => [
        $isSuperAdmin,
        $isKasir,
    ],
    'general-affair-product.view' => $isKasir,
    'general-affair-product.create' => $isKasir,
    'general-affair-product.update' => $isKasir,
    'general-affair-product.delete' => $isKasir,
    'general-affair-product.restore' => $isSuperAdmin,

    // GeneralAffairCategory Access Rules
    'general-affair-category.index' => static fn (Actor $actor): bool => true,
    'general-affair-category.data' => [
        $isSuperAdmin,
        $isKasir,
    ],
    'general-affair-category.view' => $isKasir,
    'general-affair-category.create' => $isKasir,
    'general-affair-category.update' => $isKasir,
    'general-affair-category.delete' => $isKasir,
    'general-affair-category.restore' => $isSuperAdmin,

    // GeneralAffairLocation Access Rules
    'general-affair-location.index' => static fn (Actor $actor): bool => true,
    'general-affair-location.data' => [
        $isSuperAdmin,
        $isKasir,
    ],
    'general-affair-location.view' => $isKasir,
    'general-affair-location.create' => $isKasir,
    'general-affair-location.update' => $isKasir,
    'general-affair-location.delete' => $isKasir,
    'general-affair-location.restore' => $isSuperAdmin,

    // GeneralAffair Access Rules
    'general-affair.index' => static fn (Actor $actor): bool => true,
    'general-affair.data' => [
        $isSuperAdmin,
        $isKasir,
    ],
    'general-affair.view' => $isKasir,
    'general-affair.create' => $isKasir,
    'general-affair.update' => $isKasir,
    'general-affair.delete' => $isKasir,
    'general-affair.restore' => $isSuperAdmin,

    // Attendance Reporting System
    'attendance.show' => $isKasir,
    'attendance.report' => $isKasir,

    // Branch Access Rules
    'branch.index' => static fn (Actor $actor): bool => true,
    'branch.data' => [
        $isSuperAdmin,
        $isKasir,
    ],
    'branch.view' => $isKasir,
    'branch.create' => $isKasir,
    'branch.update' => $isKasir,
    'branch.delete' => $isKasir,
    'branch.restore' => $isSuperAdmin,

    // EmployeeLeave Access Rules
    'employee-leave.index' => static fn (Actor $actor): bool => true,
    'employee-leave.data' => [
        $isSuperAdmin,
        $isKasir,
    ],
    'employee-leave.view' => $isKasir,
    'employee-leave.create' => $isKasir,
    'employee-leave.update' => $isKasir,
    'employee-leave.delete' => $isKasir,
    'employee-leave.restore' => $isSuperAdmin,

    // EmployeeGrade Access Rules
    'employee-grade.index' => static fn (Actor $actor): bool => true,
    'employee-grade.data' => [
        $isSuperAdmin,
        $isKasir,
    ],
    'employee-grade.view' => $isKasir,
    'employee-grade.create' => $isKasir,
    'employee-grade.update' => $isKasir,
    'employee-grade.delete' => $isKasir,
    'employee-grade.restore' => $isSuperAdmin,

    // AttendancePolicies Access Rules
    'attendance-policies.index' => static fn (Actor $actor): bool => true,
    'attendance-policies.data' => [
        $isSuperAdmin,
        $isKasir,
    ],
    'attendance-policies.view' => $isKasir,
    'attendance-policies.create' => $isKasir,
    'attendance-policies.update' => $isKasir,
    'attendance-policies.delete' => $isKasir,
    'attendance-policies.restore' => $isSuperAdmin,
];