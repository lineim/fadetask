<?php
namespace app\tests\Common;

use app\common\toolkit\ModuleTrait;
use support\Db;

class DataGenerater
{
    use ModuleTrait;

    public static function userGenerater(array $fields = [])
    {
        $default = [
            'uuid' => 'unittestuuid',
            'email' => 'unittest@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest',
            'mobile' => '18011112222',
            'hired_time' => time(),
            'title' => 'test',
            'created_time' => time()
        ];
        $user = array_merge($default, $fields);
        return DB::table('user')->insertGetId($user);
    }

    public static function createKanban(array $fields = [])
    {
        $default = [
            'name' => 'UnitTest Kanban',
            'desc' => 'A kanban for unit test',
            'user_id' => 1,
            'is_closed' => 0,
            'created_time' => time()
        ];

        $kanban = array_merge($default, $fields);
        return Db::table('kanban')->insertGetId($kanban);
    }

    public static function createTask(array $task = [], $listId = 0, $kanbanId = 0, $userId = 0)
    {
        $self = new self();
        if (!$userId) {
            $userId = DataGenerater::userGenerater([]);
        }
        if (!$kanbanId) {
            $kanbanId = $self->getKanbanModule()->create('UnitTest', 'Unit Test Board', $userId);
        }
        
        $kanban = $self->getKanbanModule()->get($kanbanId);
        $kanbanList = $self->getKanbanModule()->getKanbanList($kanbanId);

        $listIds = [];
        foreach ($kanbanList as $l) {
            $listIds[] = $l->id;
        }
        $defaultTask = ['title' => 'unittest', 'desc' => '', 'kanbanId' => $kanban->id];
        $task = array_merge($defaultTask, $task);

        $listId = array_pop($listIds);
        return $self->getKanbanTaskModule()->createTask($task, $userId, $listId);
    }

}
