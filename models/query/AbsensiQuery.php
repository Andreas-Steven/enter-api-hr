<?php

namespace app\models\query;

/**
 * This is the ActiveQuery class for [[\app\models\Absensi]].
 *
 * @see \app\models\Absensi
 */
class AbsensiQuery extends \yii\db\ActiveQuery
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
}
