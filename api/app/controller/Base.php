<?php
namespace app\controller;

use app\common\exception\BusinessException;
use app\common\toolkit\ModuleTrait;
use support\bootstrap\Log;
use support\bootstrap\Redis;
use support\Response;
use support\Request;

class Base
{

    use ModuleTrait;

    protected function getUser(Request $request)
    {
        return request()->session()->get('user', []);
    }

    protected function isAdmin(Request $request)
    {
        $user = $this->getUser($request);
        $role = $user['role'] ?? '';
        return 'ADMIN' == $role;
    }

    protected function json($data, $code = 0, $msg = '', $options = JSON_UNESCAPED_UNICODE)
    {
        $resData = ['code' => $code, 'data' => $data, 'msg' => $msg];
        return new Response(200, ['Content-Type' => 'application/json'], json_encode($resData, $options));
    }

    protected function msgpack($data, $code = 0, $msg = '')
    {
        $resData = \msgpack_pack(['code' => $code, 'data' => $data, 'msg' => $msg]);
        return new Response(200, ['Content-Type' => 'application/msgpack'], $resData);
    }

    protected function isKanbanMemberByTaskId($taskId, $userId)
    {
        $task = $this->getKanbanTaskModule()->getTask($taskId, ['kanban_id']);
        if (!$task) {
            throw new BusinessException('task not found');
        }
        return $this->getKanbanModule()->isMember($task->kanban_id, $userId);
    }

    protected function getLogger($channel = 'default')
    {
        return Log::channel($channel);
    }

    protected function getStorageRedis()
    {
        return Redis::connection('storage');
    }

}
