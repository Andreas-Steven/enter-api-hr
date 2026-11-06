<?php

declare(strict_types=1);

/**
 * Application messages (Indonesian)
 */
return [
    /*
     * Add your application-specific messages here
     */

    'success' => 'Berhasil',
    'array.must_contain' => '{resource} harus berisi {field}',

    /**
     * Attendance
     */
    'attendance.time_empty' => 'Waktu Mulai atau Waktu Selesai tidak boleh kosong.',
    'attendance.period.invalid_date_format' => 'Format tanggal tidak valid: {date}. Gunakan Y-m-d.',

    /**
     * Attendance Device
     */
    'invalid.latitude' => 'Latitude harus antara -90 dan 90.',
    'invalid.longitude' => 'Longitude harus antara -180 dan 180.',
    'invalid.location_zero' => 'Location tidak boleh nol.',

    /**
     * Attendance Permission
     */
    'attendance_permission.invalid_approval_transition' => 'Transisi persetujuan tidak valid dari {from} ke {to}',
    'attendance_permission.invalid_permission_id' => 'ID izin tidak valid: {permission_id}. Harus salah satu dari: {must_be}',
    'attendance_permission.start_time_required' => 'Waktu Mulai Revisi diperlukan untuk jenis izin: {type}',
    'attendance_permission.end_time_required' => 'Waktu Selesai Revisi diperlukan untuk jenis izin: {type}',

    /**
     * Employee Leave
     */
    'employee_leave.quota_exceeded' => 'Sisa kuota cuti tidak cukup. Sisa: {remaining}, diminta: {requested} (permission_id: {permission_id}).',
    'employee_leave.invalid_total_day' => 'total_day harus bilangan bulat positif. Diberikan: {total_day}.',

    /**
     * Employee Debt
     */
    'employee_debt.invalid_approval_transition' => 'Transisi persetujuan tidak valid dari {from} ke {to}',

    /**
     * Payroll Deduction
     */
    'payroll_deduction.employee_id_mismatch' => 'Employee ID tidak cocok dengan Employee ID pada EmployeeDebt.',

    /**
     * Attendance Policy
     */
    'attendance_policy.invalid_format' => 'Format {resource} tidak valid.',
    'attendance_policy.invalid_policy_value' => 'Nilai {resource} tidak valid.',

    /**
     *  Work Time
     */
    'work_time.invalid_format' => 'Format {resource} pada hari {day} tidak valid.',

    /**
     * Employee
     */
    'employee.invalid_phone' => 'Nomor Telepon wajib diisi, harus diawali dengan 62, minimal 9 digit, dan maksimal 15 digit.',
    'employee.contract_end_date_required' => 'contract_end_date wajib diisi untuk tipe karyawan kontrak.',

    /**
     * Work Pattern
     */
    'work_pattern.invalid_format' => 'Format {resource} tidak valid.',
    'work_pattern.invalid_shift_id' => 'ID shift tidak valid. Harus berupa angka positif.',
    'work_pattern.shift_not_found' => 'Shift dengan ID {id} tidak ditemukan.',
    'work_pattern.work_pattern_rule_not_found' => 'Work Pattern Rule dengan ID {id} tidak ditemukan.',
];