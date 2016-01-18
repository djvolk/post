<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "log".
 *
 * @property integer $id
 * @property string $field
 * @property integer $value
 */
class Log extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['field', 'value'], 'required'],
            [['value'], 'integer'],
            [['field'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'field' => 'Field',
            'value' => 'Value',
        ];
    }

    /**
     * @inheritdoc
     * @return LogQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new LogQuery(get_called_class());
    }
}
