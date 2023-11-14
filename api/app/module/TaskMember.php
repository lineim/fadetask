<?php
namespace app\module;

use app\common\exception\AccessDeniedException;
use app\model\KanbanTaskMember as Model;
use app\model\KanbanTask as TaskModel;

class TaskMember extends BaseModule
{

    public function getTaskMembers($taskId, $fields = ['*'])
    {
        return Model::where('task_id', $taskId)->get($fields);
    }

    public function getTasksMembers($taskIds, $fields = ['*']): \Illuminate\Support\Collection
    {
        return Model::whereIn('task_id', $taskIds)->get($fields);
    }

    public function getTaskMembersInfo($taskId, $fields = ['*'])
    {
        $tasksMembers = $this->getTasksMembersInfo([$taskId], $fields);

        return $tasksMembers[$taskId] ?? [];
    }

    public function getTasksMembersInfo(array $taskIds, $fields = ['*'])
    {
        $members = Model::whereIn('task_id', $taskIds)->get(['task_id', 'member_id']);
        $userIds = $members->pluck('member_id');
        $users = [];
        if ($userIds->toArray()) {
            $users = $this->getUserModule()->getByUserIds($userIds->toArray(), $fields);
        }

        $indexUsers = [];
        foreach ($users as $u) {
            $indexUsers[$u->id] = $u;
        }
        $tasksMembers = [];
        foreach ($members as $m) {
            if (!isset($tasksMembers[$m->task_id])) {
                $tasksMembers[$m->task_id] = [];
            }
            $memberInfo = $indexUsers[$m->member_id] ?? [];
            if ($memberInfo) {
                $tasksMembers[$m->task_id][] = $memberInfo;
            }
        }
        return $tasksMembers;
    }

    public function addMember($taskId, $memberId)
    {
        if (!$this->getKanbanModule()->isKanbanMemberByTaskId($memberId, $taskId)) {
            throw new AccessDeniedException();
        }
        $m = new Model();
        $m->member_id = $memberId;
        $m->task_id = $taskId;

        $add = $m->save();
        if ($add) {
            $this->getKanbanTaskModule()->waveTaskMemberCount($taskId, 1);
        }
        return $add;
    }

    public function getUsersKanbanTaskIds($kanbanId, array $userIds)
    {
        $memberModel = new Model();
        $taskModel = new TaskModel();
        return $memberModel->join($taskModel->getTable(), $memberModel->getTable() . '.task_id', '=', $taskModel->getTable() . '.id')
            ->where($taskModel->getTable() . '.kanban_id', $kanbanId)
            ->whereIn($memberModel->getTable() . '.member_id', $userIds)
            ->get('task_id')
            ->pluck('task_id')
            ->toArray();
    }

    public function removeMember($taskId, $memberId)
    {
        $rm = Model::where('task_id', $taskId)->where('member_id', $memberId)->delete();
        if ($rm) {
            $this->getKanbanTaskModule()->waveTaskMemberCount($taskId, -1);
        }
        return $rm;
    }

    public function removeMembers($taskId, array $memberIds)
    {
        $rm = Model::where('task_id', $taskId)->whereIn('member_id', array_values($memberIds))->delete();
        if ($rm) {
            $this->getKanbanTaskModule()->waveTaskMemberCount($taskId, count($memberIds));
        }
        return $rm;
    }

    public function countTaskMembers($taskId) : int
    {
        return Model::where('task_id', $taskId)->count();
    }

}