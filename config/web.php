<?php

$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'basic',
    'language' => 'zh-CN',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'w_XgiP2UABykBkV4c2vczfMc0inQhlwV',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
//        'user' => [
//            'identityClass' => 'app\models\User',
//            'enableAutoLogin' => true,
//        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        // 重写Yii2-user的视图
        'view' => [
            'theme' => [
                'pathMap' => [
                    '@dektrium/user/views' => '@app/views/yii2-user'
                ],
            ],
        ],
        'db' => require(__DIR__ . '/db.php'),
    ],
    'modules' => [
        'user' => [
            'class' => 'dektrium\user\Module',
            'confirmWithin' => 21600,
            'cost' => 12,
            'admins' => ['admin'],
            // 重写控制器
            'enableConfirmation' => false,
            // 重写Yii2-user的控制器
            'controllerMap' => [
                'admin' => 'app\controllers\user\AdminController',
                'profile' => 'app\controllers\user\ProfileController',
                'recovery' => 'app\controllers\user\RecoveryController',
                'registration' => 'app\controllers\user\RegistrationController',
                'security' => 'app\controllers\user\SecurityController',
                'setting' => 'app\controllers\user\SettingsController',
            ],
            // 重写Yii2-user的模型
            'modelMap' => [
                'UserSearch' => 'app\models\user\UserSearch',
                'User' => 'app\models\user\User',
                'Token' => 'app\models\user\Token',
                'SettingsForm' => 'app\models\user\SettingsForm',
                'ResendForm' => 'app\models\user\ResendForm',
                'RegistrationForm' => 'app\models\user\RegistrationForm',
                'RecoveryForm' => 'app\models\user\RecoveryForm',
                'Profile' => 'app\models\user\Profile',
                'LoginForm' => 'app\models\user\LoginForm',
                'Account' => 'app\models\user\Account',
            ],
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'allowedIPs' => ['127.0.0.1', '::1', '192.168.0.*', '192.168.178.20'] // 按需调整这里
    ];
}

return $config;
