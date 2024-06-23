<?php
namespace app\middleware;

use app\common\toolkit\ModuleTrait;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class WorkspaceAuthCheck extends MiddlewareInterface 
{

    use ModuleTrait;

    public function process(Request $request, callable $next) : Response
    {
        $user = $request->session()->get('user');
        if (!$this->getWorkspaceModule()->isUserBelongWorkspace($user->id, $user->current_workspace_id)) {
            return new Response(
                403, 
                ['Content-Type' => 'application/json'], 
                json_encode(['code' => '403', 'msg' => 'Access Denied!'], JSON_UNESCAPED_UNICODE)
            );
        }

        return $next($request);
    }
}
