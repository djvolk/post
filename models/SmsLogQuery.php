<?php

namespace app\models;

/**
 * This is the ActiveQuery class for [[SmsLog]].
 *
 * @see SmsLog
 */
class SmsLogQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }*/

    /**
     * @inheritdoc
     * @return SmsLog[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return SmsLog|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}