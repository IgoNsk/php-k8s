<?php
require_once __DIR__.'/vendor/autoload.php';

$worker = new \Kohkimakimoto\Worker\Worker();

// Monthly kpi report
$worker->job(
    "update_ratings",
    [
        'cron_time' => '0 8 1 * *',
        'max_processes' => 1,
        'command' => "php yii hello"
    ]
);
$worker->job(
    "cleanup_old_tokens",
    [
        'cron_time' => '0 3 * * *',
        'max_processes' => 1,
        'command' => 'php yii cleanup'
    ]
);

$worker->start();