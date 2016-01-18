<?php
/* @var $this yii\web\View */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\models\Settings;
use yii\helpers\VarDumper;
use yii\helpers\Url;
use kartik\grid\GridView;
use yii\web\View;
use kartik\editable\Editable;
use app\models\Parcels;

$this->title = 'Проверка работы почты';

$this->registerJs('
    $(".hint").tooltip();
    $(".grid-action").tooltip();
    $("body").on("click", ".grid-action", function (e) {
        if (confirm($(this).attr("message"))) {
            var href = $(this).attr("href");
            var self = this;
            $.get(href, function () {
            var pjax_id = $(self).closest(".pjax-wraper").attr("id");
                $.pjax.reload("#" + pjax_id);
            });
           return false;
        }
        else
        {
            return false;
        }
    });', View::POS_END);
?>
<div class="site-index">
    <div class="body-content">
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="alert alert-success col-lg-12">
                <?= Yii::$app->session->getFlash('success') ?>
            </div>
        <?php endif; ?>
        <div class="col-lg-12">
            <?php
            $gridColumns = [
                [
                    'class' => '\kartik\grid\DataColumn',
                    'attribute' => 'order_id',
                    'format' => 'raw',
                    'value' => function ($model, $key, $index, $widget) {
                        return Html::a($model->order_id, urldecode(str_replace('[orderID]', $model->order_id, Settings::findOne(['field' => 'app_order_path'])->value)));
                    },
                        ],
                        [
                            'class' => '\kartik\grid\DataColumn',
                            'attribute' => 'shipping_type',
                        ],
                        [
                            'class' => '\kartik\grid\DataColumn',
                            'attribute' => 'shop_status',
                        ],
                        [
                            'class' => '\kartik\grid\DataColumn',
                            'attribute' => 'status_time',
                        ],
                        [
                            'class' => '\kartik\grid\DataColumn',
                            'attribute' => 'order_amount',
                        ],
                        [
                            'class' => '\kartik\grid\DataColumn',
                            'attribute' => 'spi',
                        ],
                        [
                            'class' => 'kartik\grid\ExpandRowColumn',
                            'width' => '50px',
                            'value' => function ($model, $key, $index, $column) {
                                return GridView::ROW_COLLAPSED;
                            },
                            'detailUrl' => Url::to(['site/parceldetail']),
                            'headerOptions' => ['class' => 'kartik-sheet-style'],
                            'expandOneOnly' => true,
                            'detailRowCssClass' => 'nohover',
                        ],
                        [
                            'class' => 'kartik\grid\EditableColumn',
                            'editableOptions' => function ($model, $key, $index) {
                                return [
                                    'header' => 'Cтатус посылки',
                                    'size' => 'md',
                                    'inputType' => \kartik\editable\Editable::INPUT_DROPDOWN_LIST,
                                    'data' => Parcels::listDeliveryStatus(),
                                ];
                            },
                                    'vAlign' => 'middle',
                                    'hAlign' => 'center',
                                    'attribute' => 'delivery_status',
                                    'filterType' => GridView::FILTER_SELECT2,
                                    'filter' => Parcels::listDeliveryStatus(),
                                    'filterInputOptions' => ['prompt' => 'Все'],
                                    'filterWidgetOptions' =>
                                    [
                                        'pluginOptions' => ['allowClear' => true],
                                        'hideSearch' => true,
                                    ],
                                    'format' => 'raw',
                                ],
                                [
                                    'class' => 'kartik\grid\BooleanColumn',
                                    'attribute' => 'mailed',
                                    'value' => function ($model, $key, $index, $widget) {
                                        if ($model->mailed == 'yes')
                                            return true;
                                        else
                                            return false;
                                    },
                                    'vAlign' => 'middle',
                                    'trueLabel' => 'Отправлено',
                                    'falseLabel' => 'Не отправлено',
                                ],
                                [
                                    'class' => 'kartik\grid\BooleanColumn',
                                    'attribute' => 'status',
                                    'value' => function ($model, $key, $index, $widget) {
                                        if ($model->status == 'enabled')
                                            return true;
                                        else
                                            return false;
                                    },
                                    'vAlign' => 'middle',
                                    'trueLabel' => 'Отслеживается',
                                    'falseLabel' => 'Не отслеживается',
                                    'trueIcon' => '<span class="glyphicon glyphicon-eye-open"></span>',
                                    'falseIcon' => '<span class="glyphicon glyphicon-eye-close"></span>',
                                ],
                                [
                                    'class' => 'kartik\grid\ActionColumn',
                                    'template' => '{mail}{status}',
                                    'buttons' => [
                                        'mail' => function ($url, $model) {
                                            return Html::a('<span class="glyphicon glyphicon-envelope"></span>', $url, ['title' => 'Отправить email с заявкой', 'message' => 'Хотите отправить email с заявкой?', 'class' => 'grid-action', 'style' => 'margin-right:10px;']);
                                        },
                                                'status' => function ($url, $model) {
                                            if ($model->status == 'enabled')
                                                return Html::a('<span class="glyphicon glyphicon-eye-close"></span>', $url, ['title' => 'Перестать отслеживать заказ', 'message' => 'Хотите перестать отслеживать заказ?', 'class' => 'grid-action']);
                                            else
                                                return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', $url, ['title' => 'Начать отслеживать заказ', 'message' => 'Хотите начать отслеживать заказ?', 'class' => 'grid-action']);
                                        },
                                            ],
                                            'urlCreator' => function ($action, $model, $key, $index) {
                                        if ($action === 'mail')
                                        {
                                            $url = Url::to(['site/mailrequest', 'id' => $model->id]); // your own url generation logic
                                            return $url;
                                        }
                                        if ($action === 'status')
                                        {
                                            $url = Url::to(['site/changemailstatus', 'id' => $model->id]); // your own url generation logic
                                            return $url;
                                        }
                                    },
                                            'headerOptions' => ['class' => 'kartik-sheet-style'],
                                        ],
                                    ];

                                    \yii\widgets\Pjax::begin(['options' => ['class' => 'pjax-wraper']]);
                                    echo GridView::widget([
                                        'dataProvider' => $dataProvider,
                                        'filterModel' => $searchModel,
                                        'columns' => $gridColumns,
                                        'pjax' => true,
                                        'resizableColumns' => false,
                                        'toolbar' => [
                                            ['content' =>
                                                '<form id="main-table" action="/" method="post" role="form"><input type="hidden" name="_csrf" value="<?=Yii::$app->request->getCsrfToken()?>" />'.
                                                Html::submitButton('Отправить письма', ['class' => 'btn btn-info hint', 'name' => 'send-mails', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'style' => 'margin-left:10px;', 'title' => 'Отправить письма']).
                                                Html::submitButton('Обновить статусы', ['class' => 'btn btn-warning hint', 'name' => 'update-status', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'style' => 'margin-left:10px;', 'title' => 'Проверяет сроки доставки. Внимание, длительная работа функции!']).
                                                Html::submitButton('Обновить заказы', ['class' => 'btn btn-success hint', 'name' => 'update-orders', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'style' => 'margin-left:10px;', 'title' => 'Обновление информации об уже загруженных заказах']).
                                                Html::submitButton('Добавить заказы', ['class' => 'btn btn-primary hint', 'name' => 'new-orders', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'style' => 'margin-left:10px;', 'title' => 'Загрузка новых заказов из магазина']).
                                                '</form>'
                                            ]
                                        ],
                                        'responsive' => true,
                                        'hover' => true,
                                        'headerRowOptions' => ['style' => ''],
                                        'rowOptions' => function ($model, $key, $index, $grid) {
                                    if ($model->delivery_status == 'Опоздание. Не доставлено' || $model->delivery_status == 'Опоздание. Доставлено')
                                        return ['class' => GridView::TYPE_DANGER];
                                    if ($model->delivery_status == 'Возврат' || $model->delivery_status == 'Возврат. Вовремя.' || $model->delivery_status == 'Возврат. Опоздание.')
                                        return ['class' => GridView::TYPE_WARNING];
                                    if ($model->delivery_status == 'Вовремя. Доставлено')
                                        return ['class' => GridView::TYPE_SUCCESS];
                                    if ($model->delivery_status == 'В пути')
                                        return ['class' => GridView::TYPE_INFO];
                                },
                                        'panel' => [
                                            'type' => GridView::TYPE_PRIMARY,
                                            'heading' => 'Заказы',
                                            'afterOptions' => ['class' => 'text-right'],
                                            'after' => false,
                                        ],
                                    ]);
                                    \yii\widgets\Pjax::end();
                                    ?>
        </div>

    </div>
</div>


<?php
// $wsdlurl = 'https://tracking.russianpost.ru/rtm34?wsdl';
// $client2 = '';

// $client2 = new SoapClient($wsdlurl, array('trace' => 1, 'soap_version' => SOAP_1_2));

// $params3 = array ('OperationHistoryRequest' => array ('Barcode' => 'RA644000001RU', 'MessageType' => '0','Language' => 'RUS'),
//                   'AuthorizationHeader' => array ('login'=>'myLogin','password'=>'myPassword'));

// $result = $client2->getOperationHistory(new SoapParam($params3,'OperationHistoryRequest'));

// foreach ($result->OperationHistoryData->historyRecord as $record) {
//     printf("<p>%s </br>  %s, %s</p>",
//     $record->OperationParameters->OperDate,
//     $record->AddressParameters->OperationAddress->Description,
//     $record->OperationParameters->OperAttr->Name);
// };
?>

