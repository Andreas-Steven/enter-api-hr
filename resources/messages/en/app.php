<?php

declare(strict_types=1);

/**
 * Application messages (English)
 */
return [
    /*
     * Add your application-specific messages here
     */

    'success' => 'Success',
    'array.must_contain' => 'The {resource} must contain {field}',

    /**
     * Attendance
     */
    'attendance.time_empty' => 'Start Time or End Time cannot be empty.',
    'attendance.period.invalid_date_format' => 'Invalid date format: {date}. Use Y-m-d.',

    /**
     * Attendance Device
     */
    'invalid.latitude' => 'Latitude must be between -90 and 90.',
    'invalid.longitude' => 'Longitude must be between -180 and 180.',
    'invalid.location_zero' => 'Location cannot be zero.',

    /**
     * Attendance Permission
     */
    'attendance_permission.invalid_approval_transition' => 'Invalid approval transition from {from} to {to}',
    'attendance_permission.invalid_permission_id' => 'Invalid permission ID: {permission_id}. Must be one of: {must_be}',
    'attendance_permission.start_time_required' => 'Revision Start Time is required for permission type: {type}',
    'attendance_permission.end_time_required' => 'Revision End Time is required for permission type: {type}',

    /**
     * Attendance Policy
     */
    'attendance_policy.invalid_format' => 'The {resource} format is invalid.',
    'attendance_policy.invalid_minutes' => 'The {resource} must be between 0 and 1440 minutes.',
    'attendance_policy.invalid_policy_value' => 'The {resource} value is invalid.',

    /**
     *  Work Time
     */
    'work_time.invalid_format' => 'The {resource} on {day} format is invalid.',

    /**
     * Employee
     */
    'employee.invalid_phone' => 'Phone Number is required, must start with 62, minimum 9 digits, and maximum 15 digits.',
];