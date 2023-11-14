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
    'debug' => env('APP_DEBUG', false),
    'unit_test' => env('UNIT_TEST', false), // 单元测试环境
    'default_timezone' => 'Asia/Shanghai',
    'kanban_url' => env('KANBAN_URL', ''), // 看板url
    'kanban_invite_url' => env('KANBAN_INVITE_URL', ''), // 邀请注册时，用户携带token访问此地址，完成邀请
    'project_invite_url' => env('PROJECT_INVITE_URL', ''), // 邀请加入项目链接
    'kanban_forget_pass_url' => env('KANBAN_FORGET_PASS_URL', ''), // 重置密码时时，用户携带token访问此地址，密码重置
];
