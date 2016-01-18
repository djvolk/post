<?php

$params = require(__DIR__.'/params.php');

$config = [
    'id'         => 'CheckRussianPost',
    'name'       => 'Проверка работы почты',
    'basePath'   => dirname(__DIR__),
    'bootstrap'  => ['log'],
    'language'   => 'ru-RU',
    'components' => [
        'request'      => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'RFo97Mz9uUB4Qflykby6S7WPeDS6GjXr',
            'baseUrl'             => '',
        ],
        'urlManager'   => [
            'enablePrettyUrl' => true,
            'showScriptName'  => false,
            'rules'           => [
                ''         => 'site/index',
                '<action>' => 'site/<action>',
            ],
        ],
        'assetManager' => [
            'basePath' => '@webroot/assets',
            'baseUrl'  => '@web/assets'
        ],
        'cache'        => [
            'class' => 'yii\caching\FileCache',
        ],
        'user'         => [
            'identityClass'   => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer'       => [
            'class'            => 'yii\swiftmailer\Mailer',
        ],
        'log'          => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets'    => [
                [
                    'class'  => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db'           => require(__DIR__.'/db.php'),
    ],
    'modules'    => [
        'gridview' => [
            'class' => '\kartik\grid\Module'
        // enter optional module parameters below - only if you need to
        // use your own export download action or custom translation
        // message source
        // 'downloadAction' => 'gridview/export/download',
        // 'i18n' => []
        ]
    ],
    'params'     => $params,
];

if (YII_ENV_DEV)
{
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = 'yii\debug\Module';

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class'      => 'yii\gii\Module',
        'allowedIPs' => ['*'],
    ];
}

return $config;
