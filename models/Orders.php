<?php

namespace app\models;

use app\models\Parcels;
use app\models\Orders;
use app\models\Settings;
use app\models\SmsLog;
use Yii;

/**
 * This is the model class for table "orders".
 *
 * @property integer $id
 * @property integer $order_id
 * @property string $spi
 * @property string $fio
 * @property string $phone
 * @property string $amount
 * @property string $shop_status
 * @property integer $statusID
 * @property string $delivery_status
 * @property string $delivery_time
 * @property string $sms_status
 * @property string $status
 */
class Orders extends \yii\db\ActiveRecord {

    public static function tableName() {
        return 'orders';
    }

    public function rules() {
        return [
            [['order_id'], 'required'],
            [['order_id', 'statusID'], 'integer'],
            [['fio', 'status'], 'string'],
            [['delivery_time', 'send_sms_time'], 'safe'],
            [['spi', 'phone', 'amount', 'shop_status', 'delivery_status'], 'string', 'max' => 255]
        ];
    }

    public function attributeLabels() {
        return [
            'id' => 'ID',
            'order_id' => 'ID',
            'spi' => 'ШПИ',
            'fio' => 'ИО',
            'phone' => 'Телефон',
            'amount' => 'Сумма',
            'shop_status' => 'Shop Status',
            'statusID' => 'Status ID',
            'delivery_status' => 'Доставка',
            'delivery_time' => 'Delivery Time',
            'sms_status' => 'Статус',
            'status' => 'Отслеживание',
        ];
    }

    public static function find() {
        return new OrdersQuery(get_called_class());
    }

    public static function getNewShopOrders() {                                 //получить заказы из магазина
        //формируем условия по статусам и типам отправления из настроек
        $statusID = implode(',', unserialize(Settings::findOne(['field' => 'order_status_sms'])->value));

        $db = Settings::getDbShopConnect();
        if ($db)
        {
            //получаем заказы из таблицы магазина по условиям
            $orders = $db->createCommand('SELECT orderID,
                                                 order_amount,
                                                 customerID,
                                                 customer_firstname,
                                                 spi FROM SC_orders WHERE spi != "" AND statusID IN ('.$statusID.') ')
                    ->queryAll();
        }

        $count = 0;                                                             //счетчик добавленных заказов

        foreach ($orders as $order)
        {
            //проверка есть ли уже такой заказ в системе
            if (Orders::find()->where(['order_id' => $order['orderID']])->one() == NULL)
            {
                //проверка доставлен ли заказ
                $delivery = Orders::checkDeliveryStatus($order['spi']);

                //var_dump($delivery);
                //запись в систему нового доставленного заказа
                if ($delivery['status'] == true)
                {
                    $phone = $db->createCommand("SELECT reg_field_value FROM SC_customer_reg_fields_values WHERE `reg_field_ID` = '1' AND `customerID` = ".$order['customerID'])->queryOne();
                    $otch = $db->createCommand("SELECT reg_field_value FROM SC_customer_reg_fields_values WHERE `reg_field_ID` = '4' AND `customerID` = ".$order['customerID'])->queryOne();

                    $model = new Orders;
                    $model->order_id = $order['orderID'];
                    $model->spi = $order['spi'];
                    $model->fio = $order['customer_firstname'].' '.$otch['reg_field_value'];
                    $model->phone = $phone['reg_field_value'];
                    $model->amount = $order['order_amount'];
                    $model->delivery_time = $delivery['delivery_time'];
                    $model->delivery_status = $delivery['delivery_status'];

                    $statuses = Orders::OrderProcessing($model);                //отправляем SMS 1
                    $model->send_sms_time = $statuses['send_sms_time'];
                    $model->sms_status = $statuses['sms_status'];
                    $model->status = $statuses['status'];
                    if ($model->save())
                        $count++;                                               //заказ добавлен, счетчик +1
                }
            }
        }
        return $count;
    }

//загружаем новые заказы в систему    

    public static function updateOrders() {

        $query = Orders::find()                                                //получаем id заказов
                ->where(['status' => 'enabled'])
                ->orderBy('id')
                ->all();

        $count = 0;
        foreach ($query as $model)
        {
            if ($model->send_sms_time > 0 && (time() - strtotime($model->send_sms_time)) > (60 * 60 * 24 * 6))
            {
                $statuses = Orders::OrderProcessing($model);                //отправляем SMS
                $model->send_sms_time = $statuses['send_sms_time'];
                $model->sms_status = $statuses['sms_status'];
                $model->status = $statuses['status'];
                if ($model->save())
                    $count++;
            }
        }
        return $count;
    }

//обновляем заказы в систему     

    public static function OrderProcessing($model) {
        if ($model->delivery_status == 'Доставлено')
        {
            $result['status'] = 'enabled';
            if (empty($model->sms_status))
            {
                $text = Orders::GenerateSms('sms_text1', $model);
                if (Orders::SendSms($text, $model))
                    $result['sms_status'] = 'SMS 1 отправлено';
                else
                    $result['sms_status'] = 'Ошибка отправки SMS';

                $date = new \DateTime();
                $result['send_sms_time'] = $date->format('Y-m-d H:i:s');
            }

            if (!empty($model->sms_status) && !empty($model->send_sms_time))
            {
                if ($model->sms_status == 'SMS 1 отправлено')
                {
                    $text = Orders::GenerateSms('sms_text2', $model);
                    if (Orders::SendSms($text, $model))
                        $result['sms_status'] = 'SMS 2 отправлено';
                    else
                        $result['sms_status'] = 'Ошибка отправки SMS';

                    $date = new \DateTime();
                    $result['send_sms_time'] = $date->format('Y-m-d H:i:s');
                }
                elseif ($model->sms_status == 'SMS 2 отправлено')
                {
                    $text = Orders::GenerateSms('sms_text3', $model);
                    if (Orders::SendSms($text, $model))
                        $result['sms_status'] = 'SMS 3 отправлено';
                    else
                        $result['sms_status'] = 'Ошибка отправки SMS';

                    $date = new \DateTime();
                    $result['send_sms_time'] = $date->format('Y-m-d H:i:s');
                }
                elseif ($model->sms_status == 'SMS 3 отправлено')
                {
                    $text = Orders::GenerateSms('sms_text4', $model);
                    if (Orders::SendSms($text, $model))
                        $result['sms_status'] = 'SMS 4 отправлено';
                    else
                        $result['sms_status'] = 'Ошибка отправки SMS';

                    $date = new \DateTime();
                    $result['send_sms_time'] = $date->format('Y-m-d H:i:s');
                }
                elseif ($model->sms_status == 'SMS 4 отправлено')
                {
                    $result['sms_status'] = 'Не забрали';
                    $result['status'] = 'disabled';
                    $result['send_sms_time'] = $model->send_sms_time;
                }
            }
        } elseif ($model->delivery_status == 'Вручено')
        {
            $result['sms_status'] = 'Забрали';
            $result['status'] = 'disabled';
            if (!empty($model->send_sms_time))
                $result['send_sms_time'] = $model->send_sms_time;
            else
                $result['send_sms_time'] = '';
        }

        Orders::SmsLog($model, $result);
        return $result;
    }

//функция обработки заказа (отправка смс, возврат статуса)

    public static function checkDeliveryStatus($spi) {

        $OperationHistory = Parcels::getOperationHistory($spi);
        if ($OperationHistory)
        {
            $result['status'] = false;
            foreach ($OperationHistory as $history)
            {
                if ($history->operationAttribute == 'Прибыло в место вручения')
                {
                    $result['delivery_time'] = strtotime($history->operationDate);
                    $result['delivery_status'] = 'Доставлено';
                    $result['status'] = true;
                }
                if ($history->operationAttribute == 'Вручение')
                {
                    $result['$delivery_time'] = strtotime($history->operationDate);
                    $result['delivery_status'] = 'Вручено';
                    $result['status'] = false;
                }
            }
        } else
            $result['status'] = false;

        return $result;
    }

//проверка статуса доставки

    public static function GenerateSms($field, $model) {

        $field = Settings::findOne([
                    'field' => $field,
        ]);
        $text = $field->value;

        $text = str_replace("#ORDER#", $model->order_id, $text);
        $text = str_replace("#FIO#", $model->fio, $text);
        $text = str_replace("#SPI#", $model->spi, $text);
        $text = str_replace("#AMOUNT#", $model->amount, $text);
        $text = str_replace("#DATE#", $model->delivery_time, $text);

        return $text;
    }

//генерация сообщения    

    public static function SendSms($text, $order) {
        $settings = Settings::getSettings();

        $number = $order->phone;
        $number = preg_replace("#[^0-9]#", "", $number); // стерли хрень, оставили цифры
        if ($number[0] != '+')
        {
            $number[0] = 7;
            $number = '+'.$number;
        }

        $src = '<?xml version="1.0" encoding="UTF-8"?>
    <SMS>
        <operations>
        <operation>SEND</operation>
        </operations>
        <authentification>
        <username>'.$settings['app_smslogin'].'</username>
        <password>'.$settings['app_smspassword'].'</password>
        </authentification>
        <message>
        <sender>SMS</sender>
        <text>'.$text.'</text>
        </message>
        <numbers>
        <number>+79277323848</number>
        </numbers>
    </SMS>';
        /* ПчелкинДом */
        $Curl = curl_init();
        $CurlOptions = array(
            CURLOPT_URL => 'https://my.atompark.com/sms/xml.php',
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_POST => true,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 100,
            CURLOPT_POSTFIELDS => array('XML' => $src),
        );
        curl_setopt_array($Curl, $CurlOptions);
        if (false === ($Result = curl_exec($Curl)))
        {
            $result = false;
        } else
            $result = true;

        curl_close($Curl);
        //$result = true;
        return $result;
    }

//функция отправки SMS

    public static function SmsLog($order, $result) {

        $model = new SmsLog;
        $model->order_id = $order->order_id;
        $date = new \DateTime();
        $model->time = $date->format('Y-m-d H:i:s');
        $model->status = $result['sms_status'];
        $model->save();
    }

//пишем лог    

    public static function listSmsStatus() {

        $array = [
            'Забрали' => 'Забрали',
            'Не забрали' => 'Не забрали',
            'SMS 1 отправлено' => 'SMS 1 отправлено',
            'SMS 2 отправлено' => 'SMS 2 отправлено',
            'SMS 3 отправлено' => 'SMS 3 отправлено',
            'SMS 4 отправлено' => 'SMS 4 отправлено',
            'SMS 5 отправлено' => 'SMS 5 отправлено',
            'Ошибка отправки SMS' => 'Ошибка отправки SMS',
        ];

        return $array;
    }

//доступные статусы (sms_status)
}
