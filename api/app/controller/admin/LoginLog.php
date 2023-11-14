<?php
namespace app\controller\admin;

use app\controller\Base;
use support\Request;
use stdClass;

class LoginLog extends Base
{
    public function search(Request $request)
    {
        $page = $request->get('current', 1);
        $pageSize = $request->get('pageSize', 10);

        $logs = $this->getUserModule()->getLoginLogs([], ($page-1)*$pageSize, $pageSize);
        $userIds = [];
        foreach ($logs as $log) {
            if ($log->user_id) {
                $userIds[] = $log->user_id;
            }
        }
        $users = $this->getUserModule()->getByUserIds($userIds, ['id', 'name', 'email', 'mobile']);
        $userIndexed = [];
        foreach ($users as $user) {
            $userIndexed[$user->id] = $user;
        }

        foreach ($logs as $log) {
            $log->created_time = date('Y-m-d H:i:s', $log->created_time);
            $log->user_name = '';
            $log->email = '';
            if (isset($userIndexed[$log->user_id])) {
                $user = $userIndexed[$log->user_id];
                $log->user_name = $user->name;
                $tmp = [];
                if ($user->email) {
                    $tmp[] = $user->email;
                }
                if ($user->mobile) {
                    $tmp[] = $user->mobile;
                }
                $log->email = count($tmp) > 1 ? implode(' / ', $tmp) : array_pop($tmp);
            }
        }

        return $this->json([
            'logs' => $logs,
            'total' => $this->getUserModule()->getLoginLogsCount([]),
        ]);
    }

}
