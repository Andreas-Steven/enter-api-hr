<?php

namespace app\models\query;
use yii\db\Expression;

/**
 * This is the ActiveQuery class for [[\app\models\Absensi]].

 * @see \app\models\Absensi
 */
class AttendanceQuery extends \yii\db\ActiveQuery
{
    /*
    public function active()
    {
        return $this->andWhere('[[status]]=1');
    }
    */

    public function all($db = null)
    {
        return parent::all($db);
    }

    public function one($db = null)
    {
        return parent::one($db);
    }

    public function selectAttendance($employeeId=0)
    {
        return $this->addSelect(new Expression("id, date, period, attendance->'$employeeId' AS detail_attendance, status, detail_info, sync"));
    }
}
