<?php
namespace app\module\DataSync;

use app\common\exception\BusinessException;
use app\common\toolkit\ModuleTrait;
use app\module\DataSync\Dingtalk\UserAdapter;
use support\bootstrap\Log;
use support\bootstrap\Redis;

class DingtalkSync extends BaseSync
{
    use ModuleTrait;

    const TIME_OUT = 7200;
    const DINGTALK_USER_SYNC = 'sync:dingtalk:u';
    const DINGTALK_USER_SYNC_STAT = 'sync:dingtalk:u:stat';

    protected function getSyncLockKey()
    {
        return self::DINGTALK_USER_SYNC;
    }

    public function getSyncStatus()
    {
        $redis = Redis::connection('default');
        return $redis->hGetAll(self::DINGTALK_USER_SYNC_STAT);
    }

    public function syncUser()
    {
        $rootDepartment = 1;
        try {
            $this->syncDingtalkDepartmentsUsers($rootDepartment);
        } catch (\Exception $e) {
            throw $e;
        }
        return true;
    }

    public function getUnionIdBySnsCode($code)
    {
        try {
            $client = $this->getDingtalkClient();
            $userInfo = $client->getUserInfoByCode($code);

            return $userInfo['unionid'] ?? '';  
        } catch (\Exception $e) {
            $logger = $this->getLogger();
            $logger->error('get unionid by sns code failed: ' . $e->getMessage(), ['code' => $code]);
        } 
    }

    protected function syncDingtalkDepartmentsUsers($pid)
    {   
        $redis = Redis::connection('default');
        $client = $this->getDingtalkClient();
        $dataAdapter = UserAdapter::inst();
        $cursor = 0;
        while (true) {
            $data = $client->getDepartmentUsers($pid, $cursor);
            $hasMore = $data['has_more'] ?? false;
            $cursor = $data['next_cursor'] ?? 0;
            $users = $data['list'] ?? [];
            foreach ($users as $user) {
                $converted = $dataAdapter->convert($user);
                $bind = $converted['bind'];
                $user = $converted['user'];
                $this->handleUserData($bind, $user);
                $redis->hIncrBy(self::DINGTALK_USER_SYNC_STAT, 'synced', 1);
            }
            if (!$hasMore) {
                break;
            }
        }
        $subDepartmentIds = $client->getSubDepartmentIds($pid);
        foreach ($subDepartmentIds as $subPid) {
            $this->syncDingtalkDepartmentsUsers($subPid);
        }
    }

}
