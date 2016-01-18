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
use app\models\Orders;

$this->title = 'SMS уведомления о доставке';

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
                            'attribute' => 'spi',
                        ],
                        [
                            'class' => '\kartik\grid\DataColumn',
                            'attribute' => 'fio',
                        ],
                        [
                            'class' => '\kartik\grid\DataColumn',
                            'attribute' => 'phone',
                        ],
                        [
                            'class' => '\kartik\grid\DataColumn',
                            'attribute' => 'amount',
                        ],
                        [
                            'class' => '\kartik\grid\DataColumn',
                            'attribute' => 'delivery_status',
                        ],
                        [
                            'class' => 'kartik\grid\ExpandRowColumn',
                            'width' => '50px',
                            'value' => function ($model, $key, $index, $column) {
                                return GridView::ROW_COLLAPSED;
                            },
                            'detailUrl' => Url::to(['site/smslog']),
                            'headerOptions' => ['class' => 'kartik-sheet-style'],
                            'expandOneOnly' => true,
                            'detailRowCssClass' => 'nohover',
                        ],
                        [
                            'class' => '\kartik\grid\DataColumn',
                            'attribute' => 'sms_status',
                            'vAlign' => 'middle',
                            'hAlign' => 'center',
                            'filterType' => GridView::FILTER_SELECT2,
                            'filter' => Orders::listSmsStatus(),
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
                            'attribute' => 'status',
                            'value' => function ($model, $key, $index, $widget) {
                                if ($model->status == 'enabled')
                                    return true;
                                else
                                    return false;
                            },
//                            'filterType' => GridView::FILTER_SELECT2,
//                            'filterWidgetOptions' =>
//                            [
//                                'pluginOptions' => ['allowClear' => true],
//                                'hideSearch' => true,
//                            ],
//                            
//                            'width' => '200px',
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
                                'sms' => function ($url, $model) {
                                    return Html::a('<span class="glyphicon glyphicon-envelope"></span>', $url, ['title' => 'Отправить следующую SMS', 'message' => 'Хотите отправить SMS?', 'class' => 'grid-action', 'style' => 'margin-right:10px;']);
                                },
                                        'status' => function ($url, $model) {
                                    if ($model->status == 'enabled')
                                        return Html::a('<span class="glyphicon glyphicon-eye-close"></span>', $url, ['title' => 'Перестать отслеживать заказ', 'message' => 'Хотите перестать отслеживать заказ?', 'class' => 'grid-action']);
                                    else
                                        return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', $url, ['title' => 'Начать отслеживать заказ', 'message' => 'Хотите начать отслеживать заказ?', 'class' => 'grid-action']);
                                },
                                    ],
                                    'urlCreator' => function ($action, $model, $key, $index) {
                                if ($action === 'sms')
                                {
                                    $url = Url::to(['site/sendsms', 'id' => $model->id]); // your own url generation logic
                                    return $url;
                                }
                                if ($action === 'status')
                                {
                                    $url = Url::to(['site/changesmsstatus', 'id' => $model->id]); // your own url generation logic
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
                                        '<form id="main-table" action="/sms" method="post" role="form"><input type="hidden" name="_csrf" value="<?=Yii::$app->request->getCsrfToken()?>" />'.
                                        Html::submitButton('Обновить статусы', ['class' => 'btn btn-warning hint', 'name' => 'update-status', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'style' => 'margin-left:10px;', 'title' => 'Проверяет сроки доставки. Внимание, длительная работа функции!']).
                                        //Html::submitButton('Обновить заказы', ['class' => 'btn btn-success hint', 'name' => 'update-orders', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'style' => 'margin-left:10px;', 'title' => 'Обновление информации об уже загруженных заказах']).
                                        Html::submitButton('Добавить заказы', ['class' => 'btn btn-primary hint', 'name' => 'new-orders', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'style' => 'margin-left:10px;', 'title' => 'Загрузка новых заказов из магазина']).
                                        '</form>'
                                    ]
                                ],
                                'responsive' => true,
                                'hover' => true,
                                'headerRowOptions' => ['style' => ''],
                                'rowOptions' => function ($model, $key, $index, $grid) {
                            if ($model->sms_status == 'Забрали' || $model->delivery_status == 'Опоздание. Доставлено')
                                return ['class' => GridView::TYPE_SUCCESS];
                            elseif ($model->sms_status == 'Не забрали')
                                return ['class' => GridView::TYPE_DANGER];
                            else
                                return ['class' => GridView::TYPE_DEFAULT];
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