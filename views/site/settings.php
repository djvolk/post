<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\models\Settings;
use yii\helpers\VarDumper;
use kartik\grid\GridView;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */

$this->title = 'Настройки';
$this->params['breadcrumbs'][] = $this->title;

$this->registerJs('jQuery(".check").click(function () {
            jQuery.ajax({
                type: "POST",
                url: "site/checkdb",
                cache: false,
                data: jQuery(this).parents("form").serialize(),
                success: function (html) {
                    jQuery("#result").html(html);
                }
            });
            return false;
        });', View::POS_END);

$this->registerJs("$(function()
{
    $(document).on('click', '.btn-add', function(e)
    {
        e.preventDefault();

        var controlForm = $('#st-app'),
            currentEntry = $(this).parents('.entry:first'),
            newEntry = $(currentEntry.clone()).appendTo('.form-group-input');

        newEntry.find('input').val('');
        controlForm.find('.entry:not(:last) .btn-add')
            .removeClass('btn-add').addClass('btn-remove')
            .removeClass('btn-success').addClass('btn-danger')
            .html('<span class=\"glyphicon glyphicon-minus\"></span>');
    }).on('click', '.btn-remove', function(e)
    {
		$(this).parents('.entry:first').remove();

		e.preventDefault();
		return false;
	});
});", View::POS_END);
?>

<div class="site-settings">
    <h1><?= Html::encode($this->title) ?></h1>
    <div class="row" style="margin-top: 30px;">
        <div class="col-lg-12">     
            <div class="panel panel-primary">
                <div class="panel-heading" style="font-size: 16px;">Общие</div>
                <div class="panel-body">
                    <div class="col-lg-3">     
                        <div class="panel panel-info">
                            <div class="panel-heading" style="font-size: 16px;">БД магазина</div>
                            <div class="panel-body">
                                <?php $form = ActiveForm::begin(['id' => 'bd-connect']); ?>
                                <div class="form-group">
                                    <?= Html::label('Хост') ?>
                                    <?= Html::TextInput('db_host', $settings['db_host'], array('class' => 'form-control',)) ?>
                                </div>
                                <div class="form-group">
                                    <?= Html::label('Имя БД') ?>
                                    <?= Html::TextInput('db_name', $settings['db_name'], array('class' => 'form-control',)) ?>
                                </div>
                                <div class="form-group">
                                    <?= Html::label('Пользователь') ?>
                                    <?= Html::TextInput('db_user', $settings['db_user'], array('class' => 'form-control',)) ?>
                                </div>
                                <div class="form-group">
                                    <?= Html::label('Пароль') ?>
                                    <?= Html::passwordInput('db_password', $settings['db_password'], array('class' => 'form-control',)) ?>
                                </div>
                                <div class="form-group text-right">
                                    <?= Html::button('Проверка', ['class' => 'btn btn-warning pull-left check', 'name' => 'db-check',]) ?>
                                    <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary', 'name' => 'db-button']) ?>
                                </div>
                                <div id="result" class="form-group"></div>
                                <?php ActiveForm::end(); ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3">
                        <?php
                        $gridColumnsOSmail = [
                            [
                                'class' => 'kartik\grid\CheckboxColumn',
                                'rowSelectedClass' => GridView::TYPE_SUCCESS,
                                'name' => 'statuses_mail',
                                'checkboxOptions' => function($model, $key, $index, $column) {
                                    return [
                                        'value' => $model['statusID'],
                                        'checked' => $model['checked'] ? 'checked' : '',
                                    ];
                                },
                                    ],
                                    [
                                        'class' => '\kartik\grid\DataColumn',
                                        'attribute' => 'Статус',
                                        'value' => 'status_name_ru',
                                    ],
                                ];

                                ActiveForm::begin(['id' => 'order-status-mail']);
                                echo GridView::widget([
                                    'dataProvider' => Settings::getOrderStatusMail(),
                                    'columns' => $gridColumnsOSmail,
                                    'toolbar' => [],
                                    'responsive' => true,
                                    'hover' => true,
                                    'headerRowOptions' => ['style' => 'display:none;'],
                                    'summary' => '',
                                    'panel' => [
                                        'type' => GridView::TYPE_WARNING,
                                        'heading' => 'Статусы заказов для почты',
                                        'before' => false,
                                        'afterOptions' => ['class' => 'text-right'],
                                        'after' => Html::submitButton('Сохранить', ['class' => 'btn btn-primary', 'name' => 'os-button-mail']),
                                        'footer' => false,
                                    ],
                                ]);
                                ActiveForm::end();
                                ?>  
                            </div> 
                            <div class="col-lg-3">
                                <?php
                                $gridColumnsOSsms = [
                                    [
                                        'class' => 'kartik\grid\CheckboxColumn',
                                        'rowSelectedClass' => GridView::TYPE_SUCCESS,
                                        'name' => 'statuses_sms',
                                        'checkboxOptions' => function($model, $key, $index, $column) {
                                            return [
                                                'value' => $model['statusID'],
                                                'checked' => $model['checked'] ? 'checked' : '',
                                            ];
                                        },
                                            ],
                                            [
                                                'class' => '\kartik\grid\DataColumn',
                                                'attribute' => 'Статус',
                                                'value' => 'status_name_ru',
                                            ],
                                        ];

                                        ActiveForm::begin(['id' => 'order-status-sms']);
                                        echo GridView::widget([
                                            'dataProvider' => Settings::getOrderStatusSms(),
                                            'columns' => $gridColumnsOSsms,
                                            'toolbar' => [],
                                            'responsive' => true,
                                            'hover' => true,
                                            'headerRowOptions' => ['style' => 'display:none;'],
                                            'summary' => '',
                                            'panel' => [
                                                'type' => GridView::TYPE_WARNING,
                                                'heading' => 'Статусы заказов для SMS',
                                                'before' => false,
                                                'afterOptions' => ['class' => 'text-right'],
                                                'after' => Html::submitButton('Сохранить', ['class' => 'btn btn-primary', 'name' => 'os-button-sms']),
                                                'footer' => false,
                                            ],
                                        ]);
                                        ActiveForm::end();
                                        ?>  
                                    </div>
                                    <?php ActiveForm::begin(['id' => 'app-form']); ?>
                                    <div class="col-lg-3 form-group">
                                        <?= Html::label('SMS PILOT API KEY') ?>
                                        <?= Html::TextInput('app_smspilot_key', $settings['app_smspilot_key'], array('class' => 'form-control')) ?>
                                    </div>
                                    <div class="col-lg-3 form-group">
                                        <?= Html::label('SMS PILOT LOGIN') ?>
                                        <?= Html::TextInput('app_smslogin', $settings['app_smslogin'], array('class' => 'form-control')) ?>
                                    </div>
                                    <div class="col-lg-3 form-group">
                                        <?= Html::label('SMS PILOT PASSWORD') ?>
                                        <?= Html::TextInput('app_smspassword', $settings['app_smspassword'], array('class' => 'form-control')) ?>
                                    </div>
                                    <div class="col-lg-3 form-group">
                                        <?= Html::label('Пусть к заказу ([orderID] - id заказа)') ?>
                                        <?= Html::TextArea('app_order_path', $settings['app_order_path'], array('class' => 'form-control', 'style' => 'height:200px;')) ?>
                                    </div>
                                    <div class="form-group text-right col-lg-3">
                                        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary', 'name' => 'app-button']) ?>
                                    </div>
                                    <?php ActiveForm::end(); ?>
                                </div>
                            </div>               
                        </div>                   
                    </div>
                    <div class="row">
                        <div class="col-lg-12">     
                            <div class="panel panel-primary">
                                <div class="panel-heading" style="font-size: 16px;">Настройки email</div>
                                <div class="panel-body">
                                    <?php ActiveForm::begin(['id' => 'st-app']); ?>
                                    <div class="col-lg-6 form-group">
                                        <?= Html::label('Дней задержки') ?>
                                        <?= Html::TextInput('mail_day', $settings['mail_day'], array('class' => 'form-control',)) ?>
                                    </div>
                                    <div class="col-lg-6 form-group-input">
                                        <?= Html::label('Адреса (e-mail):') ?>
                                        <?php foreach (unserialize($settings['mails']) as $mail): ?>  
                                            <div class="entry input-group" style="margin-bottom: 15px;">                                                                                                                         
                                                <input class="form-control" name="mails[]" value="<?= $mail ?>" type="text" />
                                                <span class="input-group-btn">
                                                    <button class="btn btn-remove btn-danger" type="button">
                                                        <span class="glyphicon glyphicon-minus"></span>
                                                    </button>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                        <div class="entry input-group" style="margin-bottom: 15px;">                                                                                                                         
                                            <input class="form-control" name="mails[]" type="text" />
                                            <span class="input-group-btn">
                                                <button class="btn btn-success btn-add" type="button">
                                                    <span class="glyphicon glyphicon-plus"></span>
                                                </button>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="clearfix"></div>
                                    <div class="form-group text-right">
                                        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary', 'name' => 'mail-button']) ?>
                                    </div>
                                    <?php ActiveForm::end(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">     
                            <div class="panel panel-primary">
                                <div class="panel-heading" style="font-size: 16px;">Настройки SMS</div>
                                <div class="panel-body">
                                    <?php ActiveForm::begin(['id' => 'sms-app']); ?>
                                    <div class="col-lg-12 text-center" style="margin-bottom:15px; color: #727272;">
                                        <b>
                                            НОМЕР ЗАКАЗА - <span style="color: red; display: inline;">#ORDER#</span> ; 
                                            ИМЯ ОТЧЕСТВО - <span style="color: red; display: inline;">#FIO#</span> ; 
                                            ШПИ - <span style="color: red; display: inline;">#SPI#</span> ;
                                            СТОИМОСТЬ - <span style="color: red; display: inline;">#AMOUNT#</span> ;
                                            ДАТА ДОСТАВКИ - <span style="color: red; display: inline;">#DATE#</span>
                                        </b>
                                    </div>
                                    <div class="col-lg-3 form-group">
                                        <?= Html::label('Текст SMS 1 (посылка пришла)') ?>
                                        <?= Html::TextArea('sms_text1', $settings['sms_text1'], array('class' => 'form-control', 'style' => 'height:200px;')) ?>
                                    </div>
                                    <div class="col-lg-3 form-group">
                                        <?= Html::label('Текст SMS 2 (неделя на почте)') ?>
                                        <?= Html::TextArea('sms_text2', $settings['sms_text2'], array('class' => 'form-control', 'style' => 'height:200px;')) ?>
                                    </div>
                                    <div class="col-lg-3 form-group">
                                        <?= Html::label('Текст SMS 3 (2 недели на почте)') ?>
                                        <?= Html::TextArea('sms_text3', $settings['sms_text3'], array('class' => 'form-control', 'style' => 'height:200px;')) ?>
                                    </div>
                                    <div class="col-lg-3 form-group">
                                        <?= Html::label('Текст SMS 4 (3 недели на почте)') ?>
                                        <?= Html::TextArea('sms_text4', $settings['sms_text4'], array('class' => 'form-control', 'style' => 'height:200px;')) ?>
                                    </div>
                                    <div class="col-lg-3 form-group">
                                        <?= Html::label('Текст SMS 5 (4 недели на почте)') ?>
                                        <?= Html::TextArea('sms_text5', $settings['sms_text5'], array('class' => 'form-control', 'style' => 'height:200px;')) ?>
                                    </div>                                    
                                    <div class="clearfix"></div>
                                    <div class="form-group text-right">
                                        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary', 'name' => 'sms-button']) ?>
                                    </div>
                                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>    

</div>