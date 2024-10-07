<?php

namespace app\models\query;

/**
 * This is the ActiveQuery class for [[\app\models\WorkTime]].
 *
 * @see \app\models\WorkTime
 */
class WorkTimeQuery extends \yii\db\ActiveQuery
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
