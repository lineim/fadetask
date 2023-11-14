<?php
namespace app\module\DataSync;

use app\common\exception\BusinessException;
use app\common\toolkit\ModuleTrait;
use app\module\DataSync\Wework\UserAdapter;
use support\bootstrap\Log;
use support\bootstrap\Redis;

class WeworkSync extends BaseSync
{
    use ModuleTrait;

    const TIME_OUT = 7200;
    const WEWORK_USER_SYNC = 'sync:wework:u';
    const WEWORK_USER_SYNC_STAT = 'sync:wework:u:stat';

    protected function getSyncLockKey()
    {
        return self::WEWORK_USER_SYNC;
    }

    public function getSyncStatus()
    {
        $redis = Redis::connection('default');
        return $redis->hGetAll(self::WEWORK_USER_SYNC_STAT);
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
            $client = $this->getWeworkClient();
            return $client->getUserIdByCode($code); // userId 就是 union id
        } catch (\Exception $e) {
            $logger = $this->getLogger();
            $logger->error('get unionid by sns code failed: ' . $e->getMessage(), ['code' => $code]);
            throw $e;
        } 
    }

    protected function syncDingtalkDepartmentsUsers($pid)
    {   
        $redis = Redis::connection('default');
        $client = $this->getWeworkClient();
        $dataAdapter = UserAdapter::inst();
        $departments = $client->getDepartment();
        foreach ($departments as $depart) {
            $userIds = $client->getDepartmentUserIds($depart['id']);
            foreach ($userIds as $uid) {
                $user = $client->getUser($uid);
                $user['open_id'] = $user['userid']; // 兼容
                $converted = $dataAdapter->convert($user);
                $bind = $converted['bind'];
                $user = $converted['user'];
                $this->handleUserData($bind, $user);
                $redis->hIncrBy(self::WEWORK_USER_SYNC_STAT, 'synced', 1);
            }
        }
    }

}
