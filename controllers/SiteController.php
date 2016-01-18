<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\Settings;
use app\models\Parcels;
use app\models\Orders;
use \app\models\SmsLog;
use app\models\ParcelsSearch;
use yii\data\ActiveDataProvider;
use yii\helpers\RussianPostAPI;
use yii\helpers\Json;
use sb\prettydumper\Dumper;
use kartik\mpdf\Pdf;

class SiteController extends Controller {

    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['settings'],
                        'allow' => true,
                        'roles' => ['admin'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actions() {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex() {

        $searchModel = new ParcelsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        if (isset($_POST['new-orders']))
        {
            $count = Parcels::getNewShopOrders();
            Yii::$app->session->setFlash('success', 'Добавлено '.$count.' заказов.');
        }

        if (isset($_POST['update-orders']))
        {
            $count = Parcels::getUpdateShopOrders();
            Yii::$app->session->setFlash('success', 'Обновлено '.$count.' заказов.');
        }

        if (isset($_POST['update-status']))
        {
            $count = Parcels::getDeliveryStatus();
            Yii::$app->session->setFlash('success', 'Обновлено '.$count.' заказов.');
        }

        if (isset($_POST['send-mails']))
        {
            $closed = Parcels::find()                                                //получаем заказы
                    ->where(['status' => 'enabled'])
                    ->andWhere(['IN', 'delivery_status', ["Возврат. Вовремя.", "Вовремя. Доставлено"]])
                    ->orderBy('id')
                    ->all();
            $count_closed = 0;
            foreach ($closed as $model)
            {
                $model->status = 'disabled';
                if ($model->save())
                    $count_closed++;
            }

            $query = Parcels::find()                                                //получаем заказы
                    ->where(['status' => 'enabled'])
                    ->andWhere(['IN', 'delivery_status', ["Опоздание. Доставлено", "Опоздание. Не доставлено", "Возврат. Опоздание."]])
                    ->orderBy('id')
                    ->all();

            $count_mailed = 0;
            foreach ($query as $model)
            {
                $result = Parcels::SendMail($model);
                if ($result)
                {
                    $model->mailed = 'yes';
                    $model->status = 'disabled';
                    if ($model->save())
                        $count_mailed++;
                }
            }
            Yii::$app->session->setFlash('success', 'Отправлено '.$count_mailed.' писем. Закрыто '.$count_closed.' заказов.');
        }


        if (Yii::$app->request->post('hasEditable'))
        {
            $model = Parcels::findOne(Yii::$app->request->post('editableKey'));
            $out = Json::encode(['output' => '', 'message' => '']);
            $model->delivery_status = current($_POST['Parcels'])['delivery_status'];
            $model->save();
            $output = $_POST['Parcels']['delivery_status'];
            $out = Json::encode(['output' => $output, 'message' => '']);
            echo $out;
            return;
        }

        return $this->render('index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

    public function actionSms() {
        $searchModel = new \app\models\OrdersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        if (isset($_POST['new-orders']))
        {
            $count = Orders::getNewShopOrders();
            Yii::$app->session->setFlash('success', 'Добавлено '.$count.' заказов.');
        }

        if (isset($_POST['update-status']))
        {
            $count = Orders::updateOrders();
            Yii::$app->session->setFlash('success', 'Обновлено '.$count.' заказов.');
        }

        if (Yii::$app->request->post('hasEditable'))
        {
//            $model = Parcels::findOne(Yii::$app->request->post('editableKey'));
//            $out = Json::encode(['output' => '', 'message' => '']);
//            $model->delivery_status = current($_POST['Parcels'])['delivery_status'];
//            $model->save();
//            $output = $_POST['Parcels']['delivery_status'];
//            $out = Json::encode(['output' => $output, 'message' => '']);
//            echo $out;
//            return;
        }

        return $this->render('sms', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

    public function actionSettings() {

        $request = Yii::$app->request;

        if (isset($_POST['db-button']))             //сохранить настройки БД
        {
            foreach (Settings::find()->where(['like', 'field', 'db_'])->all() as $row)
            {
                $field = Settings::findOne([
                            'field' => $row['field'],
                ]);
                $field->value = $request->post($row['field'], '');
                $field->save();
            }
        }

        if (isset($_POST['db-check']))              //проверка подключения к БД
        {
            $db = new \yii\db\Connection([
                'dsn' => 'mysql:host='.$request->post('db_host', '').';dbname='.$request->post('db_name', ''),
                'username' => $request->post('db_user', ''),
                'password' => $request->post('db_password', ''),
                'emulatePrepare' => true,
                'charset' => 'utf8',
            ]);

            try {
                $db->open();
                Yii::$app->session->setFlash('success', 'Соединение успешно');
            } catch (\Exception $e) {
                Yii::$app->session->setFlash('error', 'Ошибка подключения: '.$e->getMessage());
            }
        }

//        if (isset($_POST['ts-button']))             //сохранить типы отправлений
//        {
//            $field = Settings::findOne([
//                        'field' => 'shipping_type',
//            ]);
//            $field->value = serialize($request->post('types', ''));
//            $field->save();
//        }

        if (isset($_POST['os-button-mail']))             //сохранить статусы для почты
        {
            $field = Settings::findOne([
                        'field' => 'order_status_mail',
            ]);
            $field->value = serialize($request->post('statuses_mail', ''));
            $field->save();
        }

        if (isset($_POST['os-button-sms']))             //сохранить статусы для sms
        {
            $field = Settings::findOne([
                        'field' => 'order_status_sms',
            ]);
            $field->value = serialize($request->post('statuses_sms', ''));
            $field->save();
        }

        if (isset($_POST['app-button']))             //сохранить общие настройки
        {
            foreach (Settings::find()->where(['like', 'field', 'app_'])->all() as $row)
            {
                $field = Settings::findOne([
                            'field' => $row['field'],
                ]);
                $field->value = $request->post($row['field'], '');
                $field->save();
            }
        }

        if (isset($_POST['mail-button']))             //сохранить настройки mail
        {
            $field = Settings::findOne([
                        'field' => 'mails',
            ]);
            $field->value = serialize(array_filter($request->post('mails', '')));
            $field->save();

            foreach (Settings::find()->where(['like', 'field', 'mail_'])->all() as $row)
            {
                $field = Settings::findOne([
                            'field' => $row['field'],
                ]);
                $field->value = $request->post($row['field'], '');
                $field->save();
            }
        }

        if (isset($_POST['sms-button']))             //сохранить настройки sms
        {
            foreach (Settings::find()->where(['like', 'field', 'sms_'])->all() as $row)
            {
                $field = Settings::findOne([
                            'field' => $row['field'],
                ]);
                $field->value = $request->post($row['field'], '');
                $field->save();
            }
        }

        $settings = Settings::getSettings();
        return $this->render('settings', [
                    'settings' => $settings,
                    'types' => $types,
                    'statuses_mail' => $statuses_mail,
                    'statuses_sms' => $statuses_sms,
        ]);
    }

    public function actionCheckdb() {

        $request = Yii::$app->request;
        $db = new \yii\db\Connection([
            'dsn' => 'mysql:host='.$request->post('db_host', '').';dbname='.$request->post('db_name', ''),
            'username' => $request->post('db_user', ''),
            'password' => $request->post('db_password', ''),
            'emulatePrepare' => true,
            'charset' => 'utf8',
        ]);

        try {
            $db->open();
            return '<div class="alert alert-success">Соединение успешно</div>';
        } catch (\Exception $e) {
            return '<div class="alert alert-danger">Ошибка подключения: '.$e->getMessage().'</div>';
        }
    }

    public function actionParceldetail() {
        if (isset($_POST['expandRowKey']))
        {
            $model = Parcels::findOne($_POST['expandRowKey']);
            $OperationHistory = Parcels::getOperationHistory($model['spi']);

            return Dumper::dump($OperationHistory);

        //     if (!$OperationHistory)
        //         return '<div class="alert alert-danger">Не удалось получить данные или нет ШПИ :-(</div>';
        //     else
        //     {
        //         //$Response = Parcels::getOperationTariff($OperationHistory);
        //         if (empty($model->delivery_period))
        //         {
        //             $Response = Parcels::getOperationTariff($OperationHistory);
        //             $delivery_period = Parcels::getDay($model->shipping_type, $Response);
        //         } else
        //             $delivery_period = $model->delivery_period;

        //         return $this->renderPartial('_loadOperationHistory', ['OperationHistory' => $OperationHistory, 'delivery_period' => $delivery_period, 'model' => $model, 'Response' => $Response]);

        //         //return $this->renderPartial('_loadOperationHistory', ['OperationHistory' => $OperationHistory]);
        //     }
         } else
             return '<div class="alert alert-danger">Неизвестная ошибка</div>';
    }

    public function actionSmslog() {
        if (isset($_POST['expandRowKey']))
        {
            $model = Orders::findOne($_POST['expandRowKey']);
            $log = SmsLog::find()->where(['order_id' => $model->order_id])->all();

            if (count($log) == 0)
                return '<div class="alert alert-danger">Лог пустой :-(</div>';
            else
                return $this->renderPartial('_loadSmsLog', ['log' => $log]);
        } else
            return '<div class="alert alert-danger">Неизвестная ошибка</div>';
    }

    public function actionChangemailstatus($id) {

        $model = Parcels::findOne($id);
        if ($model->status == 'enabled')
            $model->status = 'disabled';
        else
            $model->status = 'enabled';
        $model->save();

        return true;
    }

    public function actionChangesmsstatus($id) {

        $model = Orders::findOne($id);
        if ($model->status == 'enabled')
            $model->status = 'disabled';
        else
            $model->status = 'enabled';
        $model->save();

        return true;
    }

    public function actionMailrequest($id) {
        $model = Parcels::findOne($id);
        $result = Parcels::SendMail($model);

        if ($result)
        {
            $model->mailed = 'yes';
            $model->save();
        }
        return true;
    }

    public function actionCronmail() {
        //Parcels::getUpdateShopOrders();
//        Parcels::getNewShopOrders();
//        Parcels::getDeliveryStatus();
//        $closed = Parcels::find()                                                //получаем заказы
//                ->where(['status' => 'enabled'])
//                ->andWhere(['IN', 'delivery_status', ["Возврат. Вовремя.", "Вовремя. Доставлено"]])
//                ->orderBy('id')
//                ->all();
//        $count_closed = 0;
//        foreach ($closed as $model)
//        {
//            $model->status = 'disabled';
//            if ($model->save())
//                $count_closed++;
//        }
//
//        $query = Parcels::find()                                                //получаем заказы
//                ->where(['status' => 'enabled'])
//                ->andWhere(['IN', 'delivery_status', ["Опоздание. Доставлено", "Опоздание. Не доставлено", "Возврат. Опоздание."]])
//                ->orderBy('id')
//                ->all();
//
//        $count_mailed = 0;
//        foreach ($query as $model)
//        {
//            $result = Parcels::SendMail($model);
//            if ($result)
//            {
//                $model->mailed = 'yes';
//                $model->status = 'disabled';
//                if ($model->save())
//                    $count_mailed++;
//            }
//        }
    }

    public function actionCronsms() {

        Orders::updateOrders();
        Orders::getNewShopOrders();
    }

    public function actionSendsms() {
//        try {
//            //init the client
//            $client = new RussianPostAPI();
//            //fetch tracking info
//            var_dump($client->getOperationHistory('42382396002056', 'RUS')); //Use 'ENG' for English
//            //fetch COD payment info
//            //var_dump($client->getCODHistory('42382396002056', 'RUS'));
//        } catch (RussianPostException $e) {
//            die('Something went wrong: '.$e->getMessage()."\n");
//        }

        $OperationHistory = Parcels::getOperationHistory('42382396002056');
        $Response = Parcels::getOperationTariff($OperationHistory);
        // var_dump($Response);
    }

    public function actionTest() {

        $model = Parcels::findOne(1);
        $array = Parcels::generateMailArray($model);

        $content = $this->renderPartial('_letter', [
            'array' => $array]);

//        return $this->renderPartial('_letter', [
//            'array' => $array]);

        $pdf = new Pdf([
            'mode' => Pdf::MODE_UTF8,
            'format' => Pdf::FORMAT_A4,
            'orientation' => Pdf::ORIENT_PORTRAIT,
            'defaultFontSize' => 2,
            'destination' => Pdf::DEST_BROWSER,
            'content' => $content,
        ]);

        return $pdf->render();
    }

}
