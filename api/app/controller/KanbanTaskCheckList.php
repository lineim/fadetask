<?php
namespace app\controller;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\common\exception\InvalidParamsException;
use support\Request;

class KanbanTaskCheckList extends Base
{

    public function getTask(Request $request, $taskId)
    {
        $user = $this->getUser($request);
        $fields = ['id', 'task_id', 'title', 'is_done', 'done_time'];
        $checklists = $this->getTaskCheckListModule()->getByTaskId($taskId, $user['id'], $fields);

        return $this->json($checklists);
    }

    public function add(Request $request, $taskId)
    {
        $title = $request->post('title', '');
        $user = $this->getUser($request);
        $memberIds = $request->post('member_ids', []);
        $duedate = $request->post('due_date', "");
        $duetime = 0;
        if ($duedate) {
            $duetime = strtotime($duedate);
        }

        $id = $this->getTaskCheckListModule()->add($taskId, $title, $user['id'], $duetime, $memberIds);
        $newList = $this->getTaskCheckListModule()->get($id);

        return $this->json($newList);
    }

    public function update(Request $request, $taskId, $id)
    {
        $title = $request->post('title', '');
        $user = $this->getUser($request);

        $success = $this->getTaskCheckListModule()->changeTitle($id, $title, $user['id']);

        return $this->json($success);
    }

    public function done(Request $request, $taskId, $id)
    {
        $user = $this->getUser($request);
        $success = $this->getTaskCheckListModule()->done($id, $user['id']);

        return $this->json($success);
    }

    public function undone(Request $request, $taskId, $id)
    {
        $user = $this->getUser($request);
        $success = $this->getTaskCheckListModule()->undone($id, $user['id']);

        return $this->json($success);
    }

    public function delete(Request $request, $taskId, $id)
    {
        $user = $this->getUser($request);
        $success = $this->getTaskCheckListModule()->delete($id, $user['id']);

        return $this->json($success);
    }

    public function members(Request $request, $taskId, $id)
    {
        $user = $this->getUser($request);
        $task = $this->getKanbanTaskModule()->getTask($taskId, ['kanban_id']);

        if (!$this->getKanbanModule()->isMember($task->kanban_id, $user['id'])) {
            throw new AccessDeniedException();
        }
        $users = $this->getKanbanMemberModule()->getMembersWithUserInfo($task->kanban_id);

        $memberIds = [];
        $checkList = $this->getTaskCheckListModule()->get($id);
        if ($checkList) {
            $members = $checkList->members;
            foreach ($members as $member) {
                $memberIds[] = $member['id'];
            }
        }
        
        foreach ($users as &$m) {
            $m['isMember'] = in_array($m['id'], $memberIds);
        }
        return $this->json($users);
    }

    public function setMember(Request $request, $taskId, $id)
    {
        $user = $this->getUser($request);
        $memberId = $request->post('member_id');

        $this->getTaskCheckListModule()->addMember($id, $memberId, $user['id']);

        $member = $this->getUserModule()->getByUserId($memberId);
        $member->isMember = true;

        return $this->json($member);
    }

    public function rmMember(Request $request, $taskId, $id, $memberId)
    {
        $user = $this->getUser($request);
        return $this->json($this->getTaskCheckListModule()->removeMember($id, $memberId, $user['id']));
    }

    public function setDuetime(Request $request, $taskId, $id)
    {
        $user = $this->getUser($request);
        if (!$this->getKanbanModule()->isKanbanMemberByTaskId($user['id'], $taskId)) {
            throw new AccessDeniedException();
        }
        $dueDate = $request->post('due_date', '');
        $duetime = strtotime($dueDate);
        $this->getTaskCheckListModule()->setDuetime($id, $duetime);

        return $this->json($duetime);
    }

}
