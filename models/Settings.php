<?php

namespace app\models;

use Yii;
use yii\data\ArrayDataProvider;

/**
 * This is the model class for table "settings".
 *
 * @property integer $id
 * @property string $field
 * @property string $value
 */
class Settings extends \yii\db\ActiveRecord {

    public static function tableName() {
        return 'settings';
    }

    public function rules() {
        return [
            [['field'], 'required'],
            [['value'], 'string'],
            [['field'], 'string', 'max' => 255]
        ];
    }

    public function attributeLabels() {
        return [
            'id'    => 'ID',
            'field' => 'Field',
            'value' => 'Value',
        ];
    }

    public static function find() {
        return new SettingsQuery(get_called_class());
    }

    public function getSettings() {
        $query = Settings::find()
                ->orderBy('id')
                ->all();
        foreach ($query as $row)
        {
            $result[$row['field']] = $row['value'];
        }
        return $result;
    }

    public static function getShippingType() {

        $db = Settings::getDbShopConnect();
        if ($db)
        {
            $array = $db->createCommand('SELECT * FROM SC_shipping_methods WHERE Enabled=:enabled')
                    ->bindValue(':enabled', 1)
                    ->queryAll();
        } else
            $array = array();


        $checkedArray = unserialize(Settings::findOne(['field' => 'shipping_type'])->value);

        if (empty($checkedArray))
            $checkedArray = array();

        for ($i = 0; $i < count($array); $i++)
        {
            if (in_array($array[$i]['module_id'], $checkedArray))
                $array[$i]['checked'] = true;
            else
                $array[$i]['checked'] = false;
        }

        $provider = new ArrayDataProvider([
            'allModels' => $array
        ]);

        return $provider;
    }

    public static function getOrderStatusMail() {

        $db = Settings::getDbShopConnect();
        if ($db)
        {
            $array = $db->createCommand('SELECT * FROM SC_order_status')
                    ->queryAll();
        } else
            $array = array();

        $checkedArray = unserialize(Settings::findOne(['field' => 'order_status_mail'])->value);

        if (empty($checkedArray))
            $checkedArray = array();

        for ($i = 0; $i < count($array); $i++)
        {
            if (in_array($array[$i]['statusID'], $checkedArray))
                $array[$i]['checked'] = true;
            else
                $array[$i]['checked'] = false;
        }

        $provider = new ArrayDataProvider([
            'allModels' => $array
        ]);

        return $provider;
    }
    
    public static function getOrderStatusSms() {

        $db = Settings::getDbShopConnect();
        if ($db)
        {
            $array = $db->createCommand('SELECT * FROM SC_order_status')
                    ->queryAll();
        } else
            $array = array();

        $checkedArray = unserialize(Settings::findOne(['field' => 'order_status_sms'])->value);

        if (empty($checkedArray))
            $checkedArray = array();

        for ($i = 0; $i < count($array); $i++)
        {
            if (in_array($array[$i]['statusID'], $checkedArray))
                $array[$i]['checked'] = true;
            else
                $array[$i]['checked'] = false;
        }

        $provider = new ArrayDataProvider([
            'allModels' => $array
        ]);

        return $provider;
    }

    public function getDbShopConnect() {

        $settings = Settings::getSettings();
        $db = new \yii\db\Connection([
            'dsn'            => 'mysql:host='.$settings['db_host'].';dbname='.$settings['db_name'],
            'username'       => $settings['db_user'],
            'password'       => $settings['db_password'],
            'emulatePrepare' => true,
            'charset'        => 'utf8',
        ]);

        try {
            $db->open();
            return $db;
        } catch (\Exception $e) {
            return false;
        }
    }

}
