<?php
namespace app\middleware;

use app\common\toolkit\ModuleTrait;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class WorkspaceAuthCheck implements MiddlewareInterface 
{

    use ModuleTrait;

    public function process(Request $request, callable $next) : Response
    {
        $user = $request->session()->get('user');
        $workspaceUuid = $request->route->param('uuid', '');
        $worspace = $this->getWorkspaceModule()->getByUuid($workspaceUuid, ['id']);
        if (!$worspace) {
            return new Response(
                200, 
                ['Content-Type' => 'application/json'], 
                json_encode(['code' => '700', 'msg' => 'Workspace Not Found!'], JSON_UNESCAPED_UNICODE)
            );
        }

        // 检查用户是否属于当前工作空间
        if (!$this->getWorkspaceModule()->isUserBelongWorkspace($user->id, $worspace->id)) {
            return new Response(
                403, 
                ['Content-Type' => 'application/json'], 
                json_encode(['code' => '403', 'msg' => 'Access Denied!'], JSON_UNESCAPED_UNICODE)
            );
        }

        return $next($request);
    }
}
