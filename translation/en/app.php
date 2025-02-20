<?php

return [
    'responseLanguage' => 'Language English',

    // general
    'id' => 'ID',
    'ok' => 'OK',
    'success' => 'OK',
    'method not allowed' => 'Method not allowed',

    // auth
    'username' => 'Username',
    'password' => 'Password',
    'invalid username or password.' => 'Invalid username or password.',
    'no refresh token found.' => 'No refresh token found.',
    'the refresh token no longer exists.' => 'The refresh token no longer exists.',
    'the user no longer exists.' => 'The user no longer exists.',
    'failed to delete the refresh token.' => 'Failed to delete the refresh token.',
    'invalid token provided' => 'Invalid token provided',
    'username and password are required' => 'Username and password are required',
    'login success.' => 'Login success.',
    'token not found.' => 'Token not found.',
    'token expired.' => 'Token expired.',

    // custom error messages
    'mustArray' => '{field} must be an array.',
    'float' => 'Field {field} must be an float.',
    'mustLogin' => 'You must login to perform this action.',
    'superadminOnly' => 'You do not have permission to perform this action.',
    'updatePermission' => 'You do not have permission to update the {label} of this {tableName} because it is referenced in other data.',

    // Attendance custom error messages
    'attendanceFailed' => 'Attendance failed.',
    'endTimeNotValid' => 'Attendance end time value not valid.',
    'locationRequired' => 'Location must be set for device app.',
    'startTimeNotValid' => 'Attendance start time value not valid.',
    'emptyStartTimeEndTime' => 'Start time or end time must be filled.',

    // General Affair custom error messages
    'dateInvalid' => '{field} format must be YYYY-MM-DD.',

    // Employee custom error messages
    'phonePrefix' => 'The Phone Number must start with 62 and max 15 digits.',
    'invalidNik' => 'NIK must be 16 digits.',

    // Location Rules
    'invalidLatFormat' => 'Latitude format invalid.',
    'invalidLngFormat' => 'Langlitude format invalid.',
    'invalidLat' => 'Latitude must be between -90 and 90',
    'invalidLng' => 'Longitude must be between -180 and 180',

    // Attendance Permission Rules
    'attendancePermissionFailed' => 'Attendance Permission failed.',
    'rivisionEmpty' => 'Rivision Start Time and End Time can not be empty at same time.',
    'invalidTimeFormat' => 'The {field} must be in the format of HH:MM:SS.',
    'revisionRestriction' => 'For Revision request, Permission Type can only be set for Late or Leave Early.',
];