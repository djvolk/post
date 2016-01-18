<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "sms_log".
 *
 * @property integer $id
 * @property integer $order_id
 * @property string $time
 * @property string $status
 */
class SmsLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'sms_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id', 'time', 'status'], 'required'],
            [['order_id'], 'integer'],
            [['time'], 'safe'],
            [['status'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'time' => 'Time',
            'status' => 'Status',
        ];
    }

    /**
     * @inheritdoc
     * @return SmsLogQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SmsLogQuery(get_called_class());
    }
}
