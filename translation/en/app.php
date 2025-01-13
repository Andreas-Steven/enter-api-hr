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
    'superadminOnly' => 'You do not have permission to perform this action.',
    'updatePermission' => 'You do not have permission to update the {label} of this {tableName} because it is referenced in other data.',
    'mustLogin' => 'You must login to perform this action.',
    'mustArray' => '{field} must be an array.',

    // Attendance custom error messages
    'emptyStartTimeEndTime' => 'Start time or end time must be filled.',
    'startTimeNotValid' => 'Attendance start time value not valid.',
    'endTimeNotValid' => 'Attendance end time value not valid.',
    'locationRequired' => 'Location must be set for device app.',

    // General Affair custom error messages
    'dateInvalid' => '{field} format must be YYYY-MM-DD.',

    // Employee custom error messages
    'phonePrefix' => 'The phone number must start with 62.',
];