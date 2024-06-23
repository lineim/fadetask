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

namespace support\middleware;

use app\common\toolkit\ModuleTrait;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class AuthCheck implements MiddlewareInterface
{
    use ModuleTrait;

    public function process(Request $request, callable $next) : Response
    {
        $token = $request->header('X-Auth-Token');

        $skipPath = [
            '/api/reg',
            '/api/login',
            '/api/setting/login',
            '/api/kanban/task/attachment',
            '/api/account/email/available',
            '/api/account/resetPass/email',
            '/api/account/resetPass',
            '/api/account/mobile/available',
            '/api/account/send/reg/code',
            '/api/account/send/sms_code',
            '/api/account/login/by_sms_code',
        ];

        $request->session()->refresh();
        
        if (!$token && !in_array($request->path(), $skipPath)) {
            return new Response(
                401, 
                ['Content-Type' => 'application/json'], 
                json_encode(['code' => '401', 'msg' => 'Auth token is required'], JSON_UNESCAPED_UNICODE)
            );
        }

        $user = $request->session()->get($token, []);
        if (!$user && !in_array($request->path(), $skipPath)) {
            return new Response(
                401, 
                ['Content-Type' => 'application/json'], 
                json_encode(['code' => '401', 'msg' => 'Invalid Auth Token'], JSON_UNESCAPED_UNICODE)
            );
        }
        
        $user = $this->getUserModule()->getByUserId($user['id']); // reload user
        $request->session()->set('user', $user);

        $adminPrefix = '/api/admin';
        $role = $user['role'] ?? '';
        if (false !== stripos($adminPrefix, $request->path()) && 'ADMIN' !== $role) {
            return new Response(
                403, 
                ['Content-Type' => 'application/json'], 
                json_encode(['code' => '403', 'msg' => 'Access Denied!'], JSON_UNESCAPED_UNICODE)
            );
        }
        return $next($request);
    }
}