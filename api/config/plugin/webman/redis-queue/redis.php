<?php
return [
    'default' => [
        'host' => env('REDIS_QUEUE_HOST', 'redis://127.0.0.1:6379'),
        'options' => [
            'auth' => env('REDIS_QUEUE_PASSWORD', ''),     // 密码，可选参数
            'db' => env('REDIS_QUEUE_DB', 1),      // 数据库
            'max_attempts'  => env('REDIS_QUEUE_MAX_ATTEMPTS', 5), // 消费失败后，重试次数
            'retry_seconds' => env('REDIS_QUEUE_RETRY_INTERVAL', 5), // 重试间隔，单位秒
         ]
    ],

    'due_notify' => [
        'host' => env('REDIS_QUEUE_HOST', 'redis://127.0.0.1:6379'),
        'options' => [
            'auth' => env('REDIS_QUEUE_PASSWORD', ''),     // 密码，可选参数
            'db' => env('REDIS_QUEUE_DB', 2),      // 数据库
            'max_attempts'  => env('REDIS_QUEUE_MAX_ATTEMPTS', 5), // 消费失败后，重试次数
            'retry_seconds' => env('REDIS_QUEUE_RETRY_INTERVAL', 5), // 重试间隔，单位秒
         ]
    ],

];