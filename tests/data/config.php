<?php

use ArangoDBClient\ConnectionOptions;
use haqqi\arangodb\Connection;
use yii\helpers\ArrayHelper;

$config = [
    'class'             => Connection::class,
    'connectionOptions' => [
        ConnectionOptions::OPTION_ENDPOINT    => 'tcp://localhost:8529',
        ConnectionOptions::OPTION_AUTH_USER   => 'root',
        ConnectionOptions::OPTION_AUTH_PASSWD => '',
        ConnectionOptions::OPTION_DATABASE    => ''
    ]
];

if (is_file(__DIR__ . '/config.local.php')) {
    $config = ArrayHelper::merge($config, require __DIR__ . '/config.local.php');
}

return $config;
