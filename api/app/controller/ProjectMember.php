<?php
/**
 * This file is part of fade task project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\controller;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use support\Request;

class ProjectMember extends Base
{

    public function add(Request $request, $uuid)
    {
        $kanbanUuid = $request->post('kanban_uuid');
        $user = $this->getUser($request);

        $associte = $this->getKanbanModule()->associateToProject($kanbanUuid, $uuid, $user['id']);

        return $this->json($associte);
    }

    public function delete(Request $request, $uuid)
    {
        $project = \app\model\Project::where('uuid', (int) $uuid)
            ->where('is_deleted', 0)
            ->first();
        if (!$project) {
            return $this->json(['success']);
        }
        $project->is_deleted = 1;
        $project->save();

        return $this->json(['success']);
    }

    public function get(Request $request, $uuid)
    {
        $project = \app\model\Project::where('uuid', $uuid)
            ->where('is_deleted', 0)
            ->first();
        if (!$project) {
            return $this->json([], 404, 'project not found');
        }
        unset($project['id']);
        $project->created_date = date('Y-m-d H:i:s', $project->created_time);
        $project->creator = $this->getUserModule()->getByUserId($project->user_id, ['name']);
        return $this->json($project);
    }

    public function changeRole(Request $request, $uuid)
    {
        $user = $this->getUser($request);
        $role = $request->post('role', 2);
        $memberId = $request->post('member_id', 0);

        $change = $this->getProjectModule()->changeUserRole($uuid, $memberId, $role, $user['id']);
        return $this->json($change);
    }

    public function remove(Request $request, $uuid) 
    {
        $user = $this->getUser($request);
        $memberId = $request->post('member_id', 0);
        $removeFromKanbans = $request->post('remove_from_kanbans', 0);
        
        $rm = $this->getProjectModule()->removeMember($uuid, $memberId, $user['id'], $removeFromKanbans);

        return $this->json($rm);
    }

    public function search(Request $request, $uuid)
    {
        $user = $this->getUser($request);
        if (!$this->getProjectModule()->isMember($uuid, $user['id'])) {
            throw new AccessDeniedException();
        }
        $members = $this->getProjectModule()->getProjectMembers($uuid, ['id', 'uuid', 'name']);
        $total = $this->getProjectModule()->getProjectMembersCount($uuid);
        $data = [
            'members' => $members, 
            'total' => $total, 
            'is_manager' => $this->getProjectModule()->isManager($uuid, $user['id'])
        ];
        return $this->json($data);
    }

    public function invertLink(Request $request, $uuid)
    {
        $user = $this->getUser($request);
        $frontUrl = config('app.project_invite_url');
        if (!$frontUrl) {
            throw new BusinessException('front url error');
        }

        return $this->json($this->getProjectModule()->inviteLink($uuid, $user['id'], $frontUrl));
    }

    public function joinByToken(Request $request)
    {
        $token = $request->post('token', '');
        $user = $this->getUser($request);

        $joined = $this->getProjectModule()->joinByToken($user['id'], $token);
        return $this->json(['success' => $joined]);
    }

}
