<?php

$core_translation = require __DIR__ . '/core.php';

return array_merge($core_translation, [
    'responseLanguage' => 'Language English',

    #General
    'id' => 'ID',
    'ok' => 'OK',
    'success' => 'OK',
    'method not allowed' => 'Method not allowed',

    #Auth
    'username' => 'Username',
    'password' => 'Password',
    'token expired.' => 'Token expired.',
    'login success.' => 'Login success.',
    'token not found.' => 'Token not found.',
    'invalid token provided' => 'Invalid token provided',
    'no refresh token found.' => 'No refresh token found.',
    'mustLogin' => 'You must login to perform this action.',
    'the user no longer exists.' => 'The user no longer exists.',
    'invalid username or password.' => 'Invalid username or password.',
    'superadminOnly' => 'You do not have permission to perform this action.',
    'username and password are required' => 'Username and password are required',
    'the refresh token no longer exists.' => 'The refresh token no longer exists.',
    'failed to delete the refresh token.' => 'Failed to delete the refresh token.',
    'updatePermission' => 'You do not have permission to update the {label} of this {tableName} because it is referenced in other data.',

    #Attendance Permission Scenario
    'defaultRecordSuccess' => 'Permission has been saved successfully.',
    'defaultRecordFailed' => 'Failed to save permission.',
    'approveRecordSuccess' => 'Permission has been approved successfully.',
    'approveRecordFailed' => 'Failed to approve permission.',
    'rejectRecordSuccess' => 'Permission has been rejected successfully.',
    'rejectRecordFailed' => 'Failed to reject permission.',

    #Location Error
    'invalidLatFormat' => 'Invalid latitude format.',
    'invalidLngFormat' => 'Invalid longitude format.',
    'invalidLat' => 'Latitude must be between -90 and 90.',
    'invalidLng' => 'Longitude must be between -180 and 180.',

    #Custom Error Messages
    'array' => '{label} must be an array.',
    'float' => '{label} must be an float.',
    'invalidCondition' => '{label} is invalid.',
    'invalidImage' => 'The {label} format invalid.',
    'null' => '{label} is cannot be null or empty.',

    #Attendance Validation
    'endTimeNotValid' => 'Attendance end time is not valid.',
    'attendanceFailed' => 'Failed to process attendance data.',
    'startTimeNotValid' => 'Attendance start time is not valid.',
    'locationRequired' => 'Location must be set for attendance via the app.',
    'endTimeBeforeStartTime' => 'The end time must be later than the start time.',
    'emptyStartTimeEndTime' => 'Start time or end time must be provided for attendance.',

    #General Affair Validation
    'dateInvalid' => '{label} format must be YYYY-MM-DD.',
    'datePattern' => 'Date must be in the format YYYY-MM-DD.',
    'generalAffairFailed' => 'Failed to process General Affair data.',
    'codeFormatInvalid' => 'Code format invalid. Code must be a string between 10 and 25 characters',

    #Employee Custom Error Messages
    'invalidNik' => 'NIK must be string and must be 16 digits.',
    'invalidPhoneFormat' => 'Phone Number is required, must start with 62, minimum 9 digits, and maximum 15 digits.',

    #Attendance Permission Rules
    'revisionRequired' => '{label} is required for {value} request.',
    'invalidTimeFormat' => '{label} must be in the format of HH:MM:SS.',
    'attendanceNotFound' => 'Attendance data not found for date {date}.',
    'attendancePermissionFailed' => 'Failed to process Attendance Permission data.',
    'revisionRestriction' => 'For Revision request, Permission Type can only be set for Late or Leave Early.',
    'revisionEmpty' => 'Revision Start Time and End Time cannot be empty at the same time for {value} request.',

    # ZKTeco ADMS
    'admsRecordFailed' => 'Failed to process ZKTeco data.',
    'admsRecordSuccess' => 'Device has been saved successfully.',
]);