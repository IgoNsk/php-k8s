<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'php-k8s-app-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'runtimePath' => getenv('APP_RUNTIME_PATH') ?: '/tmp',
    'controllerNamespace' => 'app\commands',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
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
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
