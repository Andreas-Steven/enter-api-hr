<?php

namespace app\helpers;
use app\core\CoreConstants;

class Constants extends CoreConstants
{
    const DEVICE_APP = 'APP';
    const LAST_DATE = 26;

    // Attendance Status. Source: https://tanya.zemangat.com/kb/penjelasan-kode-status-kehadiran-karyawan/
    const ATTENDANCE_LATE = 'Late';
    const ATTENDANCE_LEAVE_EARLY = 'Leave Early';
    const ATTENDANCE_MISSED_CHECKOUT = 'Missed Checkout';
    const ATTENDANCE_WORK_HOLIDAY = 'Work Holiday';

    // Permission Status
    const PERMISSION_SICK = 1;
    const PERMISSION_SICK_WITH_RECEIPT = 2; 
    const PERMISSION_LEAVE_EARLY = 3;
    const PERMISSION_LATE = 4;
    const PERMISSION_ANNUAL_LEAVE = 5;

    const PERMISSION_LIST = [
        self::PERMISSION_SICK => 'Sick',
        self::PERMISSION_SICK_WITH_RECEIPT => 'Sick with receipt',
        self::PERMISSION_LEAVE_EARLY => 'Leave early',
        self::PERMISSION_LATE => 'Late',
        self::PERMISSION_ANNUAL_LEAVE => 'Annual leave',
    ];

    const REVISION_PERMISSION_LIST = [
        self::PERMISSION_LEAVE_EARLY,
        self::PERMISSION_LATE,
    ];

    // Approved Status
    Const APPROVED_DEFAULT = null;
    const APPROVED_REJECT = 0;
    const APPROVED_APPROVE = 1;
}