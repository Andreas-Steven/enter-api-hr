<?php

namespace app\helpers;
use app\core\CoreConstants;

class Constants extends CoreConstants
{
    const LAST_DATE = 26;
    const DEVICE_APP = 'APP';
    const CHECK_IN_THRESHOLD = '15:00:00';

    // Attendance Status. Source: https://tanya.zemangat.com/kb/penjelasan-kode-status-kehadiran-karyawan/
    const ATTENDANCE_LATE = 'late';
    const ATTENDANCE_LEAVE_EARLY = 'leave_early';
    const ATTENDANCE_MISSED_CHECKIN = 'missed_checkin';
    const ATTENDANCE_MISSED_CHECKOUT = 'missed_checkout';
    const ATTENDANCE_HOLIDAY = 'holiday';

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
    const APPROVED_DEFAULT = null;
    const APPROVED_REJECT = 0;
    const APPROVED_APPROVE = 1;

    const PERMISSION_APPROVAL = [
        self::APPROVED_DEFAULT => 'default',
        self::APPROVED_REJECT => 'reject',
        self::APPROVED_APPROVE => 'approve',
    ];

    const NEW = 1;
    const GOOD = 2;
    const LESS_GOOD = 3;
    const SECOND_HAND_GOOD = 4;
    const SECOND_HAND_LESS_GOOD = 5;
    const BROKEN = 6;

    const ASSET_CONDITION = [
        self::NEW => 'New',
        self::GOOD => 'Good',
        self::LESS_GOOD => 'Less Good',
        self::SECOND_HAND_GOOD => 'Second Hand Good',
        self::SECOND_HAND_LESS_GOOD => 'Second Hand Less Good',
        self::BROKEN => 'Broken',
    ];
}