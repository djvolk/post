<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\captcha\Captcha;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\ContactForm */

$this->title = 'Contact';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-contact">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (Yii::$app->session->hasFlash('contactFormSubmitted')): ?>

    <div class="alert alert-success">
        Thank you for contacting us. We will respond to you as soon as possible.
    </div>

    <p>
        Note that if you turn on the Yii debugger, you should be able
        to view the mail message on the mail panel of the debugger.
        <?php if (Yii::$app->mailer->useFileTransport): ?>
        Because the application is in development mode, the email is not sent but saved as
        a file under <code><?= Yii::getAlias(Yii::$app->mailer->fileTransportPath) ?></code>.
        Please configure the <code>useFileTransport</code> property of the <code>mail</code>
        application component to be false to enable email sending.
        <?php endif; ?>
    </p>

    <?php else: ?>

    <p>
        If you have business inquiries or other questions, please fill out the following form to contact us. Thank you.
    </p>

    <div class="row">
        <div class="col-lg-5">
            <?php $form = ActiveForm::begin(['id' => 'contact-form']); ?>
                <?= $form->field($model, 'name') ?>
                <?= $form->field($model, 'email') ?>
                <?= $form->field($model, 'subject') ?>
                <?= $form->field($model, 'body')->textArea(['rows' => 6]) ?>
                <?= $form->field($model, 'verifyCode')->widget(Captcha::className(), [
                    'template' => '<div class="row"><div class="col-lg-3">{image}</div><div class="col-lg-6">{input}</div></div>',
                ]) ?>
                <div class="form-group">
                    <?= Html::submitButton('Submit', ['class' => 'btn btn-primary', 'name' => 'contact-button']) ?>
                </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>

    <?php endif; ?>
</div>

        <div class="col-lg-3">
            <?php
//            $gridColumnsTS = [
//                [
//                    'class'            => 'kartik\grid\CheckboxColumn',
//                    'rowSelectedClass' => GridView::TYPE_SUCCESS,
//                    'name'             => 'types',
//                    'checkboxOptions'  => function($model, $key, $index, $column) {
//                        return [
//                            'value'   => $model['module_id'],
//                            'checked' => $model['checked'] ? 'checked' : '',
//                        ];
//                    }
//                        ],
//                        [
//                            'class'     => '\kartik\grid\DataColumn',
//                            'attribute' => 'Тип',
//                            'value'     => 'Name_ru',
//                        ],
//                    ];
//
//                    ActiveForm::begin(['id' => 'shipping-type']);
//                    echo GridView::widget([
//                        'dataProvider'     => Settings::getShippingType(),
//                        'filterModel'      => $searchModel,
//                        'columns'          => $gridColumnsTS,
//                        'toolbar'          => [],
//                        'responsive'       => true,
//                        'hover'            => true,
//                        'headerRowOptions' => ['style' => 'display:none;'],
//                        'summary'          => '',
//                        'panel'            => [
//                            'type'         => GridView::TYPE_PRIMARY,
//                            'heading'      => 'Типы отправлений',
//                            'before'       => false,
//                            'afterOptions' => ['class' => 'text-right'],
//                            'after'        => Html::submitButton('Сохранить', ['class' => 'btn btn-primary', 'name' => 'ts-button']),
//                            'footer'       => false,
//                        ],
//                    ]);
//                    ActiveForm::end();
                    ?>  
                </div>