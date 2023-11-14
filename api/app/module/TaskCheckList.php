<?php
namespace app\module;

use app\common\event\AsyncEvent;
use app\common\exception\AccessDeniedException;
use app\common\exception\InvalidParamsException;
use app\common\exception\ResourceNotFoundException;
use app\model\CheckListMember;
use app\model\KanbanTaskLog;
use app\model\TaskCheckList as TaskCheckListModel;
use support\bootstrap\Container;

class TaskCheckList extends BaseModule
{

    const MAX_TITLE_LEN = 256;

    const EV_MEMBER_CHANGE = 'checklist:member:change';
    const MEMBER_CHANGE_TYPE_ADD = 'add';
    const MEMBER_CHANGE_TYPE_RM = 'rm';

    public function get($id, array $fields = ['*'])
    {
        $checkList = TaskCheckListModel::where('id', $id)->where('deleted', 0)->first($fields);
        if (!$checkList) {
            return false;
        }
        $members = $this->getCheckListMembers($checkList->id);
        $memberIds = [];
        foreach ($members as $m) {
            $memberIds[] = $m->member_id;
        }

        $checkList->members = $memberIds ? $this->getUserModule()->getByUserIds($memberIds) : [];
        return $checkList;
    }

    public function getByTaskId($taskId, $userId, array $fields = ['*'])
    {
        $task = $this->getKanbanTaskModule()->getTask($taskId, ['kanban_id']);
        if (!$task) {
            throw new ResourceNotFoundException('Task Not Found');
        }
        if (!$this->getKanbanModule()->isMember($task->kanban_id, $userId)) {
            throw new AccessDeniedException();
        }

        $memberModel = new CheckListMember();
        $checkListModel = new TaskCheckListModel();

        $checklists = $checkListModel->where('task_id', $taskId)
            ->where('deleted', 0)
            ->get();
                
        $checklistIds = [];
        foreach ($checklists as $c) {
            $checklistIds[] = $c->id;
        }
        $members = $memberModel->whereIn('check_list_id', $checklistIds)->get(['member_id', 'check_list_id']);
        $memberIds = [];
        $checklistMembers = [];
        foreach ($members as $m) {
            $memberIds[] = $m->member_id;
            $checklistMembers[$m->check_list_id][] = $m->member_id;
        }
        $indexUsers = [];
        if ($memberIds) {
            $users = $this->getUserModule()->getByUserIds(array_unique($memberIds));
            foreach ($users as $user) {
                $indexUsers[$user->id] = $user;
            }
        }
        
        foreach ($checklists as &$checklist) {
            $members = [];
            if (isset($checklistMembers[$checklist->id])) {
                foreach ($checklistMembers[$checklist->id] as $memberId) {
                    if (isset($indexUsers[$memberId])) {
                        $members[] = $indexUsers[$memberId];
                    }
                }
            }
            $checklist->members = $members;
        }

        return $checklists;
    }

    public function add(int $taskId, string $title, int $creator, int $dueTime = 0, array $memberIds = [])
    {
        $this->validTitle($title);
        $task = $this->getKanbanTaskModule()->getTask($taskId, ['kanban_id']);
        if (!$task) {
            throw new ResourceNotFoundException('kanban.not_found');
        }
        if (!$this->getKanbanModule()->isMember($task->kanban_id, $creator)) {
            throw new AccessDeniedException();
        }
        /**
         * 判断checklist member是不是看板成员.
         */
        if ($memberIds && !$this->getKanbanModule()->isMembers($task->kanban_id, $memberIds)) {
            throw new AccessDeniedException();
        }

        $checklist = new TaskCheckListModel();
        $checklist->kanban_id = $task->kanban_id;
        $checklist->task_id = $taskId;
        $checklist->due_time = $dueTime;
        $checklist->title = $title;
        $checklist->creator = $creator;

        $this->beginTransaction();
        try {
            $insertCheckList =  $checklist->save();
            if (!$insertCheckList) {
                throw new \Exception("inster check list error!");
            }
            $update = $this->getKanbanTaskModule()->incrCheckListNum($taskId, 1);
            if (!$update) {
                throw new \Exception('update task checkout number error!');
            }
            $members = [];
            foreach (array_unique($memberIds) as $memberId) {
                $members[] = [
                    'check_list_id' => $checklist->id,
                    'member_id' => $memberId,
                    'creator_id' => $creator,
                    'created_time' => time()
                ];
            }
            if ($members) {
                $insertMember = CheckListMember::insert($members);
                if (!$insertMember) {
                    throw new \Exception('inert member error');
                }
            }
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }

        $log = new KanbanTaskLog();
        $log->kanban_id = $task->kanban_id;
        $log->task_id = $taskId;
        $log->change = json_encode($checklist);
        $log->user_id = $creator;
        $log->action = KanbanTask::TASK_LOG_ACTION_ADD_CHECKLIST;
        $log->created_time = time();
        $log->save();

        foreach ($memberIds as $memberId) {
            $evParams = [
                'type' => self::MEMBER_CHANGE_TYPE_ADD, 
                'checklist_id' => $checklist->id, 
                'member_id' => $memberId, 
                'creator' => $creator,
                'time' => time()
            ];
            if ($memberId != $creator) {
                $this->getAsynEvent()->emit(self::EV_MEMBER_CHANGE, $evParams);
            }
        }

        return $checklist->id;
    }

    public function getCheckListMembers($id)
    {
        return CheckListMember::where('check_list_id', $id)->get();
    }

    public function changeTitle($id, $title, $userId)
    {
        $this->validTitle($title);
        $checklist = $this->get($id, ['kanban_id']);
        if (!$checklist) {
            throw new ResourceNotFoundException('Check List Not Found!');
        }
        if (!$this->getKanbanModule()->isMember($checklist->kanban_id, $userId)) {
            throw new AccessDeniedException();
        }
        return TaskCheckListModel::where('id', $id)->update(['title' => $title]);
    }

    public function addMember($id, $memberId, $creator) : bool
    {
        if ($this->isMemberInCheckList($id, $memberId)) {
            return true;
        }
        $checkList = $this->get($id, ['id', 'kanban_id']);
        if (!$checkList) {
            throw new ResourceNotFoundException('checklist.not_found');
        }
        if (!$this->getKanbanModule()->isMember($checkList->kanban_id, $creator)) {
            throw new AccessDeniedException();
        }
        if (!$this->getKanbanModule()->isMember($checkList->kanban_id, $memberId)) {
            throw new AccessDeniedException();
        }
        $newMember = [
            'check_list_id' => $id,
            'member_id' => $memberId,
            'creator_id' => $creator,
            'created_time' => time()
        ];
        $r = CheckListMember::insert($newMember);
        $evParams = [
            'type' => self::MEMBER_CHANGE_TYPE_ADD, 
            'checklist_id' => $id, 
            'member_id' => $memberId, 
            'creator' => $creator,
            'time' => time()
        ];
        if ($memberId != $creator) {
            $this->getAsynEvent()->emit(self::EV_MEMBER_CHANGE, $evParams);
        }
        return $r;
    }

    public function removeMember($id, $memberId, $operator)
    {
        $r = CheckListMember::where('check_list_id', $id)
            ->where('member_id', $memberId)
            ->delete();
        $evParams = [
            'type' => self::MEMBER_CHANGE_TYPE_RM, 
            'checklist_id' => $id, 
            'member_id' => $memberId, 
            'creator' => $operator,
            'time' => time()
        ];
        if ($memberId != $operator) {
            $this->getAsynEvent()->emit(self::EV_MEMBER_CHANGE, $evParams);
        }
        return $r;
    }

    public function setDuetime($id, $dueTime)
    {
        $dueTime = $dueTime < 0 ? 0 : $dueTime;
        return TaskCheckListModel::where('id', $id)
            ->update(['due_time' => $dueTime]);
    }

    public function isMemberInCheckList($id, $memberId) : bool
    {
        return CheckListMember::where('check_list_id', $id)
            ->where('member_id', $memberId)
            ->exists();
    }

    public function done($id, $userId)
    {
        $checklist = $this->get($id, ['kanban_id', 'task_id', 'title', 'is_done', 'done_time']);
        if (!$checklist) {
            throw new ResourceNotFoundException('Check List Not Found!');
        }
        if (!$this->getKanbanModule()->isMember($checklist->kanban_id, $userId)) {
            throw new AccessDeniedException('Access Denied!');
        }
        if ($checklist->is_done) {
            return true;
        }

        $done = TaskCheckListModel::where('id', $id)->update(['is_done' => 1, 'done_time' => time()]);

        $log = new KanbanTaskLog();
        $log->kanban_id = $checklist->kanban_id;
        $log->task_id = $checklist->task_id;
        $log->change = json_encode($checklist);
        $log->user_id = $userId;
        $log->action = KanbanTask::TASK_LOG_ACTION_DONE_CHECKLIST;
        $log->created_time = time();
        $log->save();

        $this->getKanbanTaskModule()->incrFinishedCheckListNum($checklist->task_id, 1);

        return (bool) $done;
    }

    public function undone($id, $userId)
    {
        $checklist = $this->get($id, ['kanban_id', 'task_id', 'title', 'is_done']);
        if (!$checklist) {
            throw new ResourceNotFoundException('Check List Not Found!');
        }
        if (!$this->getKanbanModule()->isMember($checklist->kanban_id, $userId)) {
            throw new AccessDeniedException('Access Denied!');
        }
        if (!$checklist->is_done) {
            return true;
        }
        $undone = TaskCheckListModel::where('id', $id)->update(['is_done' => 0, 'done_time' => 0]);

        $log = new KanbanTaskLog();
        $log->kanban_id = $checklist->kanban_id;
        $log->task_id = $checklist->task_id;
        $log->change = json_encode($checklist);
        $log->user_id = $userId;
        $log->action = KanbanTask::TASK_LOG_ACTION_UNDONE_CHECKLIST;
        $log->created_time = time();
        $log->save();

        $this->getKanbanTaskModule()->decrFinishedCheckListNum($checklist->task_id, 1);

        return (bool) $undone;
    }

    public function delete($id, $userId)
    {
        $checklist = $this->get($id, ['kanban_id', 'task_id', 'deleted']);
        if (!$checklist) {
            throw new ResourceNotFoundException('Check List Not Found!');
        }
        if (!$this->getKanbanModule()->isMember($checklist->kanban_id, $userId)) {
            throw new AccessDeniedException();
        }
        if ($checklist->deleted) {
            return true;
        }
        $del = TaskCheckListModel::where('id', $id)->update(['deleted' => 1]);
        if (false !== $del) {
            $this->getKanbanTaskModule()->decrCheckListNum($checklist->task_id, 1);
        }
        return $del;
    }

    public function statCheckListNum($taskId)
    {
        $total = TaskCheckListModel::where('task_id', $taskId)
            ->where('deleted', 0)
            ->count();
        $finished = TaskCheckListModel::where('task_id', $taskId)
            ->where('deleted', 0)
            ->where('is_done', 1)
            ->count();

        return ['total' => $total, 'finished' => $finished];
    }

    protected function validTitle($title, $exception = true)
    {
        $title = trim($title);
        $valid = mb_strlen($title) <= self::MAX_TITLE_LEN && !empty($title);
        if (!$valid && $exception) {
            throw new InvalidParamsException('Title Invalid!');
        }
        return $valid;
    }

}
