<?php

declare(strict_types=1);

/**
 * Application messages (Indonesian)
 */
return [
    /*
     * Add your application-specific messages here
     */

    'success' => 'Success',
    'array.must_contain' => '{resource} harus berisi {field}',

    /**
     * Attendance
     */
    'attendance.period.invalid_date_format' => 'Format tanggal tidak valid: {date}. Gunakan Y-m-d.',


    /**
     * Attendance Device
     */
    'invalid.latitude' => 'Latitude harus antara -90 dan 90.',
    'invalid.longitude' => 'Longitude harus antara -180 dan 180.',

    /**
     * Attendance Permission
     */
    'attendance_permission.start_time_required' => 'Waktu Mulai Revisi diperlukan untuk tipe izin: {type}',
    'attendance_permission.end_time_required' => 'Waktu Selesai Revisi diperlukan untuk tipe izin: {type}',
    'attendance_permission.invalid_approval_transition' => 'Transisi persetujuan tidak valid dari {from} ke {to}',
    'attendance_permission.invalid_permission_id' => 'ID izin tidak valid: {permission_id}. Harus salah satu dari: {must_be}',

    /**
     *  Work Time
     */
    'work_time.invalid_format' => 'Format {resource} tidak valid',
];