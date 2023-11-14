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

return [
    'default' => [
        'host' => env('CACHE_REDIS_HOST', '127.0.0.1'),
        'password' => env('CACHE_REDIS_PASSWORD', null),
        'port' => env('CACHE_REDIS_PORT', 6379),
        'database' => env('CACHE_REDIS_DB', 0),
    ],
    'storage' => [
        'host' => env('STORAGE_REDIS_HOST', '127.0.0.1'),
        'password' => env('STORAGE_REDIS_PASSWORD', null),
        'port' => env('STORAGE_REDIS_PORT', 6379),
        'database' => env('STORAGE_REDIS_DB', 1),
    ],
];
