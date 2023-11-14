<?php
namespace app\controller;

use app\common\exception\AccessDeniedException;
use app\model\User;
use support\Request;
use app\module\Kanban as KanbanModule;

class TaskMember extends Base
{

    public function list(Request $request, $taskId)
    {
        $user = $this->getUser($request);
        if (!$this->getKanbanModule()->isKanbanMemberByTaskId($user['id'], $taskId)) {
            throw new AccessDeniedException('Access Denied!');
        }

        $members = $this->getTaskMemberModule()->getTaskMembersInfo($taskId);

        return $this->json($members);
    }

    public function add(Request $request, $taskId)
    {
        $user = $this->getUser($request);
        if (!$this->getKanbanModule()->isKanbanMemberByTaskId($user['id'], $taskId)) {
            throw new AccessDeniedException('Access Denied!');
        }
        $memberId = $request->post('member_id');
        $added = $this->getTaskMemberModule()->addMember($taskId, $memberId);
        if ($added) {
            return $this->json(true);
        }
        return $this->json(false);
    }

    public function remove(Request $request, $taskId)
    {
        $user = $this->getUser($request);
        if (!$this->getKanbanModule()->isKanbanMemberByTaskId($user['id'], $taskId)) {
            throw new AccessDeniedException('Access Denied!');
        }
        $memberId = $request->post('member_id');
        $removed = $this->getTaskMemberModule()->removeMember($taskId, $memberId);
        if ($removed) {
            return $this->json(true);
        }
        return $this->json(false);
    }

}
