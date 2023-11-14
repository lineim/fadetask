<?php
namespace app\module;

use app\common\exception\ResourceNotFoundException;
use app\common\toolkit\ModuleTrait;
use app\model\KanbanList as KanbanListModel;
use support\bootstrap\Container;
use support\bootstrap\Log;
use support\bootstrap\Redis;
use support\Db;
use app\common\event\AsyncEvent;
use app\common\toolkit\RedisLock;
use app\common\toolkit\SlidingWindowRateLimit;

abstract class BaseModule
{
    use ModuleTrait;

    protected static $instances;

    public static function inst()
    {
        $class = get_called_class();
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class();
        }
        return self::$instances[$class];
    }

    public function isKanbanMemberByTaskId($userId, $taskId)
    {
        $task = $this->getKanbanTaskModule()->getTask($taskId, ['kanban_id']);
        if (!$task) {
            throw new ResourceNotFoundException('task.not_found');
        }
        return $this->getKanbanModule()->isMember($task->kanban_id, $userId);
    }

    public function isKanbanMemberByListId($userId, $listId)
    {
        $list = KanbanListModel::where('id', $listId)
            ->get(['kanban_id']);
        if (!$list) {
            throw new ResourceNotFoundException('List Not Found');
        }
        return $this->getKanbanModule()->isMember($list->kanban_id, $userId);
    }

    public function getWriteDb()
    {
        return Db::connection('write');
    }

    /**
     * @return null
     *
     * @throws \Throwable
     */
    public function beginTransaction()
    {
        return $this->getWriteDb()->beginTransaction();
    }

    /**
     * @return null
     *
     * @throws \Throwable
     */
    public function commit()
    {
        return $this->getWriteDb()->commit();
    }

    /**
     * @return null
     *
     * @throws \Throwable
     */
    public function rollback()
    {
        return $this->getWriteDb()->rollBack();
    }

    public function getLogger()
    {
        return Log::channel('default');
    }

    public function getCacheRedis()
    {
        return Redis::connection('default');
    }

    public function getStorageRedis()
    {
        return Redis::connection('storage');
    }

    /**
     * @return RedisLock
     */
    protected function getLocker($connection = 'default')
    {
        $redis = Redis::connection($connection);
        return RedisLock::inst($redis);
    }

    /**
     * @return SlidingWindowRateLimit
     */
    protected function getRateLimit()
    {
        $redis = Redis::connection('default');
        return SlidingWindowRateLimit::inst($redis);
    }

    /**
     * @return AsyncEvent
     */
    public function getAsynEvent()
    {
        return Container::get('async.event');
    }

    private function __construct()
    {

    }

    private function __clone()
    {

    }

}
