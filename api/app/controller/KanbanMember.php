<?php
namespace app\controller;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\common\exception\ResourceNotFoundException;
use app\model\User;
use support\Request;
use app\module\Kanban as KanbanModule;

class KanbanMember extends Base
{
    public function search(Request $request, $id)
    {
        $keyword = $request->get('keyword', '');
        $forTaskId = $request->get('for_task_id', 0);
        $user = $this->getUser($request);
        $kanban = $this->getKanbanModule()->getByUuid($id, ['id']);
        if (!$kanban) {
            throw new BusinessException('kanban.not_found');
        }
        $id = $kanban->id;
        if (!$this->getKanbanModule()->isMember($id, $user['id'])) {
            throw new AccessDeniedException('Access Denied!');
        }
        $query = User::where('verified', 1);
        if ($keyword) {
            // $query = $query->where(function($query, $keyword) {
            //     $query->orWhere('name', 'like', '%' . $keyword . '%')
                    //   ->orWhere('email', 'like', '%' . $keyword . '%')
                    //   ->orWhere('mobile', 'like', '%' . $keyword . '%');
            // });
            $query->where('name', 'like', '%' . $keyword . '%');
        }
        $users = $query->get(['id', 'uuid', 'email', 'name', 'mobile', 'hired_time', 'title', 'avatar', 'role', 'created_time']);
        $members = $this->getKanbanModule()->getMembers($id);
        $membersIndex = [];
        $memberIds = [];
        
        foreach ($members as $m) {
            $memberIds[] = $m->member_id;
            $membersIndex[$m->member_id] = $m;
        }

        if ($forTaskId) {
            $taskMembers = $this->getTaskMemberModule()->getTaskMembersInfo($forTaskId, ['id']);
            $taskMemberIds = array_column($taskMembers, 'id');
        }

        foreach ($users as $key => &$u) {
            if (in_array($u->id, $memberIds)) {
                $u->kanban_id = $id;
                $u->memeber_role = $membersIndex[$u->id]['role'] ?? 3;
                if ($forTaskId && in_array($u->id, $taskMemberIds)) { // 如果是查询给指定任务用，返回搜索的成员是否在任务的成员列表中
                    $u->isMember = true;
                } else {
                    $u->isMember = false;
                }
            } else {
                unset($users[$key]);
            }
        }

        return $this->json(array_values($users->toArray()));
    }

    public function list(Request $request, $id)
    {
        $user = $this->getUser($request);
        $kanban = $this->getKanbanModule()->getByUuid($id, ['id']);
        if (!$kanban) {
            throw new BusinessException('kanban.not_found');
        }
        $id = $kanban->id;
        if (!$this->getKanbanModule()->isMember($id, $user['id'])) {
            throw new AccessDeniedException('Access Denied!');
        }
        $members = $this->getKanbanModule()->getMembersWithUserInfo($id);
        return $this->json($members);
    }

    public function admin(Request $request, $id)
    {
        $user = $this->getUser($request);
        $kanban = $this->getKanbanModule()->getByUuid($id, ['id']);
        if (!$kanban) {
            throw new BusinessException('kanban.not_found');
        }
        $id = $kanban->id;
        $isAdmin = $this->getKanbanModule()->isAdmin($id, $user['id']);
        return $this->json($isAdmin ? 1 : 0);
    }

    public function add(Request $request, $id)
    {
        $user = $this->getUser($request);
        if (!$this->getKanbanModule()->isAdmin($id, $user['id'])) {
            throw new AccessDeniedException('Access Denied!');
        }
        $newMemberId = $request->post('member_id', 0);
        if ($this->getKanbanModule()->joinKanban($id, $newMemberId, KanbanModule::MEMBER_ROLE_USER)) {
            $members = $this->getKanbanModule()->getMembers($id);
            return $this->json($members);
        }
        return $this->json(false);
    }

    public function setRoleAdmin(Request $request, $id, $memberId)
    {
        $user = $this->getUser($request);
        $uuid = $id;
        $kanban = $this->getKanbanModule()->getByUuid($uuid, ['id']);
        if (!$kanban) {
            throw new ResourceNotFoundException('kanban.not_found');
        }
        $success = $this->getKanbanModule()->setMemberAsAdmin($kanban->id, $memberId, $user['id']);
        return $this->json($success);
    }

    public function setRoleUser(Request $request, $id, $memberId)
    {
        $user = $this->getUser($request);
        $uuid = $id;
        $kanban = $this->getKanbanModule()->getByUuid($uuid, ['id']);
        if (!$kanban) {
            throw new ResourceNotFoundException('kanban.not_found');
        }
        $success = $this->getKanbanModule()->removeMemberAdminPermission($kanban->id, $memberId, $user['id']);
        return $this->json($success);
    }

    public function remove(Request $request, $id, $memberId)
    {
        $user = $this->getUser($request);
        $uuid = $id;
        $kanban = $this->getKanbanModule()->getByUuid($uuid, ['id']);
        if (!$kanban) {
            throw new ResourceNotFoundException('kanban.not_found');
        }
        $rmed = $this->getKanbanModule()->deleteKanbanMember($kanban->id, $memberId, $user['id']);
        return $this->json($rmed);
    }

    public function invite(Request $request, $id)
    {
        $frontUrl = config('app.kanban_invite_url');
        if (!$frontUrl) {
            throw new BusinessException('front url error');
        }

        $uuid = $id;
        $kanban = $this->getKanbanModule()->getByUuid($uuid, ['id']);
        if (!$kanban) {
            throw new ResourceNotFoundException('kanban.not_found');
        }

        $user = $this->getUser($request);
        $emails = $request->post('emails');
        $emails = explode(',', $emails);
        $result = $this->getKanbanMember()->invite($kanban->id, $emails, $frontUrl, $user['id']);

        return $this->json($result);
    }

    public function inviteJoin(Request $request)
    {
        $uuid = $request->post('id');
        $user = $this->getUser($request);
        $token = $request->post('token');
        try {
            $joined = $this->getKanbanMember()->joinByInvite($uuid, $user['id'], $token);
            return $this->json(['success' => $joined]);
        } catch (BusinessException $e) {
            $message = $e->getMessage();
            return $this->json(['success' => false, 'message' => $message]);
        }
    }

    public function joinLink(Request $request, $id)
    {
        $user = $this->getUser($request);

        $frontUrl = config('app.kanban_invite_url');
        if (!$frontUrl) {
            throw new BusinessException('front url error');
        }

        $link = $this->getKanbanMemberModule()->inviteUrlLink($id, $user['id'], $frontUrl);

        return $this->json(['url' => $link]);
    }

}