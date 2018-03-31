<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'php-k8s-app-api',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => 'hv6JYLOtmK4IQjaC0UAVnXbHeMBiXcS8',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
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
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => getenv('APP_REDIS_HOSTNAME'),
            'port' => 6379,
            'database' => 0,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'flushInterval' => 0,
            'targets' => [
                'stdout' => [
                    'class' => 'codemix\streamlog\Target',
                    'url' => 'php://stdout',
                    'levels' => explode(',', getenv('APP_LOG_LEVELS') ?: "error,warning"),
                    'enabled' => getenv('APP_LOG_TARGET') === 'stdout',
                ],
                'logstash' => [
                    'class' => 'app\components\GelfLogger\GraylogTarget',
                    'enabled' => getenv('APP_LOG_TARGET') === 'logstash',
                    'host' => getenv('APP_GELF_HOST'),
                    'port' => getenv('APP_GELF_PORT'),
                    'tags' => getenv('APP_GELF_TAGS'),
                    'logstashPrefix' => getenv('APP_GELF_LOGSTASH_PREFIX'),
                    'namespace' => getenv('APP_GELF_NAMESPACE'),
                    'levels' => explode(',', getenv('APP_LOG_LEVELS')),
                    'logVars' => ['_GET', '_POST', '_FILES', '_COOKIE', '_SESSION'],
                    'logVarsConverters' => [
                        'password' => function($value) {
                            return str_repeat('*', mb_strlen($value));
                        },
                    ],
                    'additionalContext' => [
                        'request_id' => function () {
                            if (!(Yii::$app instanceof \yii\web\Application)) {
                                return null;
                            }
                            return Yii::$app->request->headers['x-request-id'];
                        },
                        'user_id' => function () {
                            return Yii::$app->user->getId();
                        },
                    ]
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
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
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
