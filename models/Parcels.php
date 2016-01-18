<?php

namespace app\models;

use Yii;
use app\models\Settings;
use yii\data\ArrayDataProvider;
use yii\helpers\RussianPostAPI;
use sb\prettydumper\Dumper;
use yii\data\ActiveDataProvider;

/**
 * This is the model class for table "parcels".
 *
 * @property integer $id
 * @property integer $order_id
 * @property string $spi
 * @property string $shipping_type
 * @property string $order_time
 * @property string $shop_status
 * @property integer $statusID
 * @property string $order_amount
 * @property string $status_time
 * @property string $mailed
 * @property integer $delivery_period
 * @property string $delivery_status
 * @property string $status
 */
class Parcels extends \yii\db\ActiveRecord {

    public $OperationHistory;

    public static function tableName() {
        return 'parcels';
    }

    public function rules() {
        return [
            [['order_id'], 'required'],
            [['order_id'], 'integer'],
            [['spi', 'shipping_type'], 'string', 'max' => 255]
        ];
    }

    public function attributeLabels() {
        return [
            'id' => 'ID',
            'order_id' => 'ID',
            'spi' => 'ШПИ',
            'shipping_type' => 'Тип отправления',
            'order_time' => 'Order Time',
            'shop_status' => 'Статус заказа',
            'statusID' => 'Status ID',
            'order_amount' => 'Цена',
            'status_time' => 'Время установки статуса',
            'mailed' => 'Письмо',
            'delivery_period' => 'Delivery Period',
            'delivery_status' => 'Статус посылки',
            'status' => 'Статус',
        ];
    }

    public static function find() {
        return new ParcelsQuery(get_called_class());
    }

    public static function getNewShopOrders() {                                 //получить заказы из магазина
        //формируем условия по статусам и типам отправления из настроек
        //$shipping_module_id = implode(',', unserialize(Settings::findOne(['field' => 'shipping_type'])->value));
        $statusID = implode(',', unserialize(Settings::findOne(['field' => 'order_status_mail'])->value));

        $db = Settings::getDbShopConnect();
        if ($db)
        {
            //получаем заказы из таблицы магазина по условиям
            $orders = $db->createCommand('SELECT orderID,
                                                 order_time,
                                                 statusID,
                                                 order_amount,
                                                 spi FROM SC_orders WHERE order_time >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH) AND spi != "" AND statusID IN ('.$statusID.')')
//                    ->bindValue(':statusID', $statusID)
//                    ->bindValue(':shipping_module_id', $shipping_module_id)
                    //order_time < DATE_SUB(NOW(), INTERVAL 6 MONTH) AND
                    ->queryAll();
        }

        $count = 0;                                                             //счетчик добавленных заказов
        foreach ($orders as $order)
        {
            //проверка есть ли уже такой заказ в системе
            if (Parcels::find()->where(['order_id' => $order['orderID']])->one() == NULL)
            {
                //получаем название статуса и время его установки
                $status = $db->createCommand('SELECT status_name_ru FROM SC_order_status WHERE statusID='.$order['statusID'])->queryOne();
                $status_time = $db->createCommand('SELECT status_change_time FROM SC_order_status_changelog WHERE orderID ='.$order['orderID'].' AND status_name = "'.$status['status_name_ru'].'"')->queryOne();
                $model = new Parcels;
                $model->order_id = $order['orderID'];
                $model->order_time = $order['order_time'];
                $model->statusID = $order['statusID'];
                $model->shop_status = $status['status_name_ru'];
                $model->status_time = $status_time['status_change_time'];
                $model->order_amount = $order['order_amount'];
                $model->spi = $order['spi'];
                $model->mailed = 'no';
                $model->delivery_status = 'Не известно';
                $model->status = 'enabled';
                if ($model->save())
                    $count++;                                                   //заказ добавлен, счетчик +1
//                if ($count > 29)
//                    break;
            }
        }
        return $count;
    }

    public static function getUpdateShopOrders() {                              //обновить информацию о заказах
        $query = Parcels::find()                                                //получаем id заказов
                ->where(['status' => 'enabled'])
                ->orderBy('id')
                ->all();

        $count = 0;                                                             //счетчик обновленных заказов
        foreach ($query as $row)
        {
            $db = Settings::getDbShopConnect();
            if ($db)
            {
                $model = $row;                                                  //заполняем модель
                //запрос на проверку изменения данных
                $order = $db->createCommand('SELECT orderID,
                                                    statusID,
                                                    order_amount,
                                                    spi FROM SC_orders WHERE orderID = '.$row['order_id'].'
                                                                            AND (statusID != '.$row['statusID'].'
                                                                            OR order_amount != "'.$row['order_amount'].'"
                                                                            OR spi != "'.$row['spi'].'")')
                        ->queryOne();
                //если изменились
                if ($order)
                {
                    //проверка изменения статуса (чтобы отсечь лишние запросы)
                    if ($row['statusID'] != $order['statusID'])
                    {
                        $status = $db->createCommand('SELECT status_name_ru FROM SC_order_status WHERE statusID='.$order['statusID'])->queryOne();
                        $status_time = $db->createCommand('SELECT status_change_time FROM SC_order_status_changelog WHERE orderID ='.$order['orderID'].' AND status_name = "'.$status['status_name_ru'].'"')->queryOne();
                        $model->statusID = $order['statusID'];
                        $model->shop_status = $status['status_name_ru'];
                        $model->status_time = $status_time['status_change_time'];
                    }
                    $model->order_amount = $order['order_amount'];
                    $model->spi = $order['spi'];
                    if ($model->save())
                        $count++;                                               //заказ обновлен, +1 к счетчику
                }
            }
        }
        return $count;
    }

    public static function getOperationHistory($spi) {                          //состояние посылки
        if (!empty($spi))
        {
            try {
                //$client = new RussianPostAPI();
                //$OperationHistory = ($client->getOperationHistory($spi, 'RUS'));
                $wsdlurl = 'https://tracking.russianpost.ru/rtm34?wsdl';
                $client2 = '';
                $client2 = new SoapClient($wsdlurl, array('trace' => 1, 'soap_version' => SOAP_1_2));
                $params3 = array ('OperationHistoryRequest' => array ('Barcode' => $spi, 'MessageType' => '0','Language' => 'RUS'),
                                  'AuthorizationHeader' => array ('login'=>'myLogin','password'=>'myPassword'));
                $OperationHistory = $client2->getOperationHistory(new SoapParam($params3,'OperationHistoryRequest'));  
            } catch (\Exception $e) {
            	//var_dump($e);
                $OperationHistory = $e;
            }
        } else
        {
            $OperationHistory = false;
        }

        return $OperationHistory;
    }

    public static function getOperationTariff($OperationHistory) {                          //состояние посылки
        if ($OperationHistory)
        {
            //информация для получения тарифов
            $from = reset($OperationHistory)->operationPlacePostalCode;
            $to = reset($OperationHistory)->destinationPostalCode;
            $date = date("d.m.Y", strtotime(reset($OperationHistory)->operationDate));
            $weigth = (reset($OperationHistory)->itemWeight) * 1000;
            $value = (reset($OperationHistory)->declaredValue) * 100;

            //st=shop.mysite.ru&ml=programmer@test.ru

            try {
                //получаем информацию о тарифе (стоимость, срок и т.д.)
                $Request = 'http://api.postcalc.ru/?f='.$from.'&c=RU&st=www.pchelkindom.ru&ml=samarawebmaster@gmail.com&t='.$to.'&w='.$weigth.'&v='.$value.'&s=0&d='.$date.'&o=php';

                $field = Log::findOne(1);
                $field->value = $field->value + 1;
                $field->save();

                //распаковываем ответ
                $Response = file_get_contents($Request);
            } catch (\Exception $e) {
                $Response = false;
            }
            if ($Response)
                if (substr($Response, 0, 3) == "\x1f\x8b\x08")
                {
                    $Response = gzinflate(substr($Response, 10, -8));
                    $Response = unserialize($Response);
                }
            return $Response;
        } else
        {
            return false;
        }
    }

    public static function getDeliveryStatus() {

        $query = Parcels::find()                                                //получаем заказы
                ->where(['status' => 'enabled'])
                ->andWhere(['IN', 'delivery_status', ["Не известно", "Опоздание. Не доставлено", "В пути"]])
                ->orderBy('id')
                ->all();

        $field = Log::findOne(1);
        $field->value = 0;
        $field->save();

        $count = 0;                                                             //счетчик обновленных заказов
        foreach ($query as $row)
        {
            $OperationHistory = Parcels::getOperationHistory($row['spi']);
            if ($OperationHistory != false)
                $Response = Parcels::getOperationTariff($OperationHistory);
            else
                $Response = false;

            if (Parcels::changeDeliveryStatus($row, $OperationHistory, $Response))
                $count++;
            if ($count > 300)
                break;
        }
        return $count;
    }

    public static function changeDeliveryStatus($model, $OperationHistory, $Response) {

        if ($OperationHistory != false && $Response != false)
        {
            $shipping_type = reset($OperationHistory)->mailType;
            $delivery_period = Parcels::getDay($shipping_type, $Response);
            $start = strtotime(reset($OperationHistory)->operationDate);
            $start_code = reset($OperationHistory)->operationPlacePostalCode;

            $end = '';

            foreach ($OperationHistory as $history)
            {
                if ($history->operationAttribute == 'Прибыло в место вручения')
                {
                    $end = strtotime($history->operationDate);
                    $end_code = $history->operationPlacePostalCode;
                }
            }

            if ($delivery_period > 0)
            {
                if (!empty($end))  //доставлено
                {
                    if (($end - $start) > $delivery_period * 24 * 60 * 60)
                    {
                        $delivery_lateness = (int) ceil((($end - $start) / (24 * 60 * 60))) - (int) $delivery_period;
                        $delivery_status = 'Опоздание. Доставлено';
                    }

                    if (($end - $start) <= $delivery_period * 24 * 60 * 60)
                    {
                        $delivery_status = 'Вовремя. Доставлено';
                    }
                } else   //не доставлено
                {
                    $end = strtotime(end($OperationHistory)->operationDate);

                    if (($end - $start) > $delivery_period * 24 * 60 * 60)
                    {
                        $delivery_lateness = (int) ceil((($end - $start) / (24 * 60 * 60))) - (int) $delivery_period;
                        $delivery_status = 'Опоздание. Не доставлено';
                    }

                    if (($end - $start) <= $delivery_period * 24 * 60 * 60)
                    {
                        $delivery_status = 'В пути';
                    }
                }

                if ($start_code == $end_code)
                {
                    foreach ($OperationHistory as $history)
                    {
                        if ($history->operationAttribute == 'Прибыло в место вручения')
                        {
                            $end = strtotime($history->operationDate);
                            break;
                        }
                    }

                    if (($end - $start) > $delivery_period * 24 * 60 * 60)
                    {
                        $delivery_lateness = (int) ceil((($end - $start) / (24 * 60 * 60))) - (int) $delivery_period;
                        $delivery_status = 'Возврат. Опоздание.';
                    }

                    if (($end - $start) < $delivery_period * 24 * 60 * 60)
                    {
                        $delivery_lateness = 0;
                        $delivery_status = 'Возврат. Вовремя.';
                    }

                    if ($end == 0 || empty($end))
                    {
                        $delivery_lateness = 0;
                        $delivery_status = 'Возврат.';
                    }
                }
            }

            if (isset($delivery_period) && !empty($delivery_period))
                $model->delivery_period = $delivery_period;
            if (isset($delivery_lateness) && !empty($delivery_lateness))
                $model->delivery_lateness = $delivery_lateness;
            if (isset($delivery_status) && !empty($delivery_status))
                $model->delivery_status = $delivery_status;
            if (isset($shipping_type) && !empty($shipping_type))
                $model->shipping_type = $shipping_type;
            //$model->status = 'disabled';
            if ($model->save())
                return true;
            else
                return false;
        }
        else
        {
//            $model->status = 'disabled';
//            $model->save();           
            return false;
        }
    }

    public static function getDay($shipping_type, $Response) {

        $array = [
            'Посылка' => 'ЦеннаяПосылка',
            'Бандероль 1 класса' => 'ЦеннаяБандероль1Класс',
        ];
        if (isset($Response["Отправления"][$array[$shipping_type]]["СрокДоставки"]))
            $day = $Response["Отправления"][$array[$shipping_type]]["СрокДоставки"];
        if ($day == '')
            $day = $Response['Магистраль']['ДоставкаСтандарт'];

        if (!is_int($day))
        {
            $day = explode('-', $day);
            $day = end($day);
        }
//        if (!is_int($day) || empty($day))
//            $day = 0;

        return $day;
    }

    public static function generateMail($model, $text) {

        $OperationHistory = Parcels::getOperationHistory($model->spi);
        $OperationHistory = reset($OperationHistory);

        $marker_reason = 'X';

        if ($OperationHistory->declaredValue != 0)
        {
            $marker_ob_cen = 'X';
            $input_ob_cen = ($OperationHistory->declaredValue) * 100;
            $input_ob_cen = $input_ob_cen.' руб.';
        } else
        {
            $marker_ob_cen = '';
            $input_ob_cen = '';
        }

        if ($model->shipping_type == 'Посылка')
            $marker_posilka = 'X';
        else
            $marker_posilka = '';

        if ($model->shipping_type == 'Бандероль 1 класса')
            $marker_1class = 'X';
        else
            $marker_1class = '';

        if ($OperationHistory->collectOnDeliveryPrice != 0)
        {
            $marker_nal_platej = 'X';
            $input_nal_platej = ($OperationHistory->collectOnDeliveryPrice) * 100;
            $input_nal_platej = $input_nal_platej.' руб.';
        } else
        {
            $marker_nal_platej = '';
            $input_nal_platej = '';
        }

        $date = new \DateTime();
        $input_data = $date->format('d.m.Y');
        $input_spi = $model->spi;

        $text = str_replace("[marker_reason]", $marker_reason, $text);
        $text = str_replace("[marker_ob_cen]", $marker_ob_cen, $text);
        $text = str_replace("[input_ob_cen]", $input_ob_cen, $text);
        $text = str_replace("[marker_posilka]", $marker_posilka, $text);
        $text = str_replace("[marker_1class]", $marker_1class, $text);
        $text = str_replace("[marker_nal_platej]", $marker_nal_platej, $text);
        $text = str_replace("[input_nal_platej]", $input_nal_platej, $text);

        $text = str_replace("[input_spi]", $input_spi, $text);
        $text = str_replace("[input_data]", $input_data, $text);

        if (!empty($OperationHistory->itemWeight))
        {
            $input_weight = $OperationHistory->itemWeight;
            $text = str_replace("[input_weight]", $input_weight, $text);
        } else
            $error = $error.' ВЕС посылки пустое.';


        if (!empty($OperationHistory->operationPlaceName))
        {
            $input_ops = $OperationHistory->operationPlaceName;
            $text = str_replace("[input_ops]", $input_ops, $text);
        } else
            $error = $error.' ОПС подачи пустое.';

        $input_fio_otpr = 'ООО «Радуга» 443070 г.Самара, ул. Авроры 68/55';
        $text = str_replace("[input_fio_otpr]", $input_fio_otpr, $text);

        if (!empty($OperationHistory->Rcpn))
        {
            $input_fio_addresata = $OperationHistory->Rcpn.' '.$OperationHistory->destinationAddress;
            $text = str_replace("[input_fio_addresata]", $input_fio_addresata, $text);
        } else
            $error = $error.' Имя адресата пустое.';

        //var_dump($error);
        return $text;
    }

    public static function generateMailArray($model) {

        $OperationHistory = Parcels::getOperationHistory($model->spi);
        if ($OperationHistory != false)
        {
            $OperationHistory = reset($OperationHistory);

            $result['marker_reason'] = 'X';

            if ($OperationHistory->declaredValue != 0)
            {
                $result['marker_ob_cen'] = 'X';
                $result['input_ob_cen'] = ($OperationHistory->declaredValue);
                $result['input_ob_cen'] = number_format($result['input_ob_cen']/100, 2, ',', ' ');
                $result['input_ob_cen'] = $result['input_ob_cen'].' руб.';
            }

            if ($model->shipping_type == 'Посылка')
                $result['marker_posilka'] = 'X';

            if ($model->shipping_type == 'Бандероль 1 класса')
            {
                $result['marker_1class'] = 'X';
                $result['marker_banderol'] = 'X';
            }

            if ($OperationHistory->collectOnDeliveryPrice != 0)
            {
                $result['marker_nal_platej'] = 'X';
                $result['input_nal_platej'] = ($OperationHistory->collectOnDeliveryPrice);
                $result['input_nal_platej'] = number_format($result['input_nal_platej']/100, 2, ',', ' ');
                $result['input_nal_platej'] = $result['input_nal_platej'].' руб.';
            }

            $date = new \DateTime();
            $result['input_data'] = $date->format('d.m.Y');
            $result['input_spi'] = $model->spi;
            $result['parcel_data'] = date("d.m.Y", strtotime($OperationHistory->operationDate));

            if (!empty($OperationHistory->itemWeight))
            {
                $result['input_weight'] = $OperationHistory->itemWeight;
            } else
                $error = $error.' ВЕС посылки пустое.';


            if (!empty($OperationHistory->operationPlaceName))
            {
                $result['input_ops'] = $OperationHistory->operationPlaceName;
            } else
                $error = $error.' ОПС подачи пустое.';

            $result['input_fio_otpr'] = 'ООО «Радуга» 443070 г.Самара, ул. Авроры 68/55';

            if (!empty($OperationHistory->Rcpn))
            {
                $result['input_fio_addresata'] = $OperationHistory->Rcpn.' '.$OperationHistory->destinationAddress;
            } else
                $error = $error.' Имя адресата пустое.';
        } else
            $result = false;
        //var_dump($error);
        return $result;
    }

    public static function SendMail($model) {

        $mails = Settings::findOne([
                    'field' => 'mails',
        ]);
        $mails = unserialize($mails->value);

//        $filename = 'files/letter.php';
//        $r = fopen($filename, 'r');
//        $text .= fread($r, filesize($filename));
//
//        $text = Parcels::generateMail($model, $text);

        $array = Parcels::generateMailArray($model);

        if ($array != false)
        {
            $messages = [];
            foreach ($mails as $mail)
            {
                $messages[] = Yii::$app->mailer->compose('letter', ['imageFileName' => 'files/stamp.png', 'array' => $array])
                        ->setFrom('zakaz@pchelkindom.ru')
                        ->setSubject('Заявление на почту')
                        // ->setTextBody('Plain text content')
                        //->setBody($text)
                        //->attach('files/letter.html')
                        ->setTo($mail);
            }
            $result = Yii::$app->mailer->sendMultiple($messages);
        } else
            $result = false;

        return $result;
    }

    public static function listDeliveryStatus() {

        $array = [
            'Не известно' => 'Не известно',
            'В пути' => 'В пути',
            'Опоздание. Не доставлено' => 'Опоздание. Не доставлено',
            'Опоздание. Доставлено' => 'Опоздание. Доставлено',
            'Возврат. Опоздание.' => 'Возврат. Опоздание.',
            'Возврат. Вовремя.' => 'Возврат. Вовремя.',
            'Возврат' => 'Возврат',
            'Вовремя. Доставлено' => 'Вовремя. Доставлено',
        ];

        return $array;
    }

}
