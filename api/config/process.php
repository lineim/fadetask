<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use support\view\Raw;
use support\view\Twig;
use support\view\Blade;
use support\view\ThinkPHP;
use Workerman\Worker;

return [
    // 文件更新检测
    'monitor' => [
        'handler' => process\Monitor::class,
        'reloadable' => false,
        'constructor' => [
            // Monitor these directories
            'monitorDir' => array_merge([
                app_path(),
                config_path(),
                base_path() . '/process',
                base_path() . '/support',
                base_path() . '/resource',
                base_path() . '/.env',
            ], glob(base_path() . '/plugin/*/app'), glob(base_path() . '/plugin/*/config'), glob(base_path() . '/plugin/*/api')),
            // Files with these suffixes will be monitored
            'monitorExtensions' => [
                'php', 'html', 'htm', 'env'
            ],
            'options' => [
                'enable_file_monitor' => !Worker::$daemonize && DIRECTORY_SEPARATOR === '/',
                'enable_memory_monitor' => DIRECTORY_SEPARATOR === '/',
            ]
        ]
    ],
    'mysqlping' => [
        'handler'  => process\MysqlPing::class
    ],

    'crontab' => [
        'handler' => process\Crontab::class
    ],

    'stats_crontab' => [
        'handler' => process\StatsCrontab::class,
        'count'   => 1, // 可以设置多进程
    ],

    'redis_consumer'  => [
        'handler'     => Webman\RedisQueue\Process\Consumer::class,
        'count'       => 1, // 可以设置多进程
        'constructor' => [
            // 消费者类目录
            'consumer_dir' => app_path() . '/queue/redis'
        ]
    ],

    'due_notify' => [
        'handler' => process\DueNotify::class,
        'count' => 1,
    ],

    // 'due_notify_consumer' => [
    //     'handler'     => Webman\RedisQueue\Process\Consumer::class,
    //     'count'       => 2, // 可以设置多进程
    //     'constructor' => [
    //         // 消费者类目录
    //         'consumer_dir' => app_path() . '/queue/redis/due_notify'
    //     ]
    // ],
    
    // 其它进程
    /*'websocket'  => [
        'handler'  => process\Websocket::class,
        'listen' => 'websocket://0.0.0.0:8888',
        'count'  => 10,
    ],*/
];
