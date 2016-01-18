<?php

namespace app\models;

/**
 * This is the ActiveQuery class for [[Parcels]].
 *
 * @see Parcels
 */
class ParcelsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }*/

    /**
     * @inheritdoc
     * @return Parcels[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Parcels|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}