<?php
namespace app\module\DataSync;

use support\bootstrap\Log;
use support\bootstrap\Redis;
use app\module\BaseModule;
use Ramsey\Uuid\Uuid;
use app\model\User;
use app\model\UserBind;
use app\common\thirdPartClient\dingtalk\Client as DingtalkClient;
use app\common\thirdPartClient\wework\Client as WeworkClient;
use app\common\exception\BusinessException;
use app\common\toolkit\RedisLock;
use support\Db;

abstract class BaseSync extends BaseModule
{
    const LOCK_TTL = 86000;

    abstract protected function getSyncLockKey();
    abstract protected function syncUser();
    abstract public function getSyncStatus();

    public function lock()
    {
        $locker = $this->getLocker();
        return $locker->lock($this->getSyncLockKey(), self::LOCK_TTL);
    }

    public function unlock()
    {
        $locker = $this->getLocker();
        return $locker->release($this->getSyncLockKey());
    }

    public function sync()
    {
        $logger = $this->getLogger();
        $lock = $this->lock();
        if (!$lock) {
            throw new BusinessException('正在同步中，请稍后重试 ...');
        }
        try {
            $this->syncUser();
            $this->unlock();
        } catch (\Exception $e) {
            $this->unlock();
            throw $e;
        }
        $logger->info('sysc finished', $this->getSyncStatus());
    }

    protected function handleUserData($bind, $user)
    {
        $logger = $this->getLogger();
        $db = Db::connection('write');
        $db->beginTransaction();
        try {
            $binded = UserBind::where('type', $bind['type'])->where('union_id', $bind['union_id'])->first(['user_id']);
            if ($binded) {
                $userId = $binded->user_id;
                $updateUserFields = [];
                foreach ($user as $k => $v) {
                    if (in_array($k, ['name', 'mobile', 'hired_time', 'title', 'verified', 'avatar', 'role'])) {
                        $updateUserFields[$k] = $v;
                    }
                }
                $update = $this->getUserModule()->updateUser($userId, $updateUserFields);
                $db->commit();
                return $update;
            }

            if (empty($user['email'])) {
                $user['email'] = $bind['type'] . '-' . $bind['union_id'] . '@sys.com';
            }

            $passwordHash = password_hash(trim($bind['union_id'] . rand(0, 100000)), PASSWORD_BCRYPT);
            $uuid = Uuid::uuid4();
            $user['uuid'] = $uuid->toString();
            $user['passhash'] = $passwordHash;
            $user['created_time'] = time();

            $newUser = new User($user);
            $newUser->save();

            $bind['user_id'] = $newUser->id;
            $bind['created_time'] = time();
            $newBind = new UserBind($bind);
            $newBind->save();
            $db->commit();
            return true;
        } catch (\PDOException $e) {
            $db->rollBack();
            $logger->error('sync user error: ' . $e->getMessage(), ['bind' => $bind, 'user' => $user]);
            throw new \Exception('An Pdo Exception Occurred, See the log for more information!');
        } catch (\Exception $e) {
            $db->rollBack();
            $logger->error('sync user error: ' . $e->getMessage(), ['bind' => $bind, 'user' => $user]);
            throw $e;
        }
    }

    protected function getDingtalkClient() : DingtalkClient
    {
        $logger = $this->getLogger();
        $client = DingtalkClient::inst($logger);
        $cache = Redis::connection('default');
        $client->setCache($cache);

        $config = $this->getSettingModule()->getDingtalkSetting(true);
        if (!$config->enabled) {
            throw new BusinessException('dingtalk not opened!');
        }

        $client->setConfig($config->syncAppKey, $config->syncAppSecret, $config->loginAppId, $config->loginAppSecret);
        $client->setDebug(env('APP_DEBUG', false));

        return $client;
    }

    protected function getWeworkClient() : WeworkClient
    {
        $logger = $this->getLogger();
        $client = WeworkClient::inst($logger);
        $cache = Redis::connection('default');
        $client->setCache($cache);

        $config = $this->getSettingModule()->getWeworkSetting(true);
        if (!$config->enabled) {
            throw new BusinessException('dingtalk not opened!');
        }

        $logger->debug(json_encode($config));

        $client->setConfig($config->agentId, $config->secret);
        $client->setCropInfo($config->corpId, $config->secret);
        $client->setDebug(env('APP_DEBUG', false));

        return $client;
    }

}
