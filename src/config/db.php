<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => getenv('APP_DATABASE'),
    'enableSchemaCache' => !YII_DEBUG,
    'emulatePrepare' => true,
    'attributes' => [
        /**
         * @see http://php.net/manual/ru/features.persistent-connections.php
         */
        PDO::ATTR_PERSISTENT => false,
    ],
];
