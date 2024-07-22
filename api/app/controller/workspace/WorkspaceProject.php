<?php
namespace app\controller\workspace;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\controller\Base;
use support\Request;

class WorkspaceProject extends Base
{
    
    public function list(Request $request, $uuid)
    {
        $keywords = $request->input('keywords');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);

        $projects = $this->getProjectModule()
            ->listWorkspaceProjects($uuid, ['keywords' => $keywords], ['id', 'desc'], $page, $perPage);

        $userIds = [];
        foreach ($projects->items() as $project) {
            $userIds[] = $project->user_id;
        }
        $users = [];
        $usersIndexed = [];
        if ($userIds) {
            $users = $this->getUserModule()->getByUserIds($userIds, ['id', 'name', 'email']);
            foreach ($users as $user) {
                $usersIndexed[$user->id]= $user;
            }
        }
        
        foreach ($projects->items() as &$project) {
            $project->creator = $usersIndexed[$project->user_id] ?? [];
        }
        return $this->json($projects);
    }

    public function listForTree(Request $request, $uuid)
    {
        $user = $this->getUser();
        $keywords = $request->get('keywords', '');
        $workspace = $this->getWorkspaceModule()->getByUuid($uuid, ['id']);
        if (!$workspace) {
            throw new BusinessException('workspace.not_found');
        }

        $projects = $this->getProjectModule()
            ->getUserCanAccessProjectsInWorkspace($user['id'], $workspace->id, $keywords);

        $userIds = [];
        foreach ($projects as $project) {
            $userIds[] = $project->user_id;
        }
        $users = [];
        $usersIndexed = [];
        if ($userIds) {
            $users = $this->getUserModule()->getByUserIds($userIds, ['id', 'name', 'email']);
            foreach ($users as $user) {
                $usersIndexed[$user->id]= $user;
            }
        }
        foreach ($projects as &$project) {
            $project->creator = $usersIndexed[$project->user_id] ?? [];
        }
        return $this->json($projects);
    }

    public function overview(Request $request, $uuid, $spaceUuid)
    {
        $user = $this->getUser();
        $field = ['name', 'uuid', 'description', 'member_num', 'kanban_num', 'is_public', 'created_time'];
        $space = $this->getProjectModule()->getProjectByUuid($spaceUuid, $field);
        if (!$space) {
            throw new BusinessException('space.not_found');
        }
        if (!$this->getProjectModule()->isMember($spaceUuid, $user['id'])) {
            throw new AccessDeniedException();
        }
        
        $overview = $this->getProjectStatModule()->overview($spaceUuid);
        $overview['space'] = $space;
        return $this->json($overview);
    }

    public function add(Request $request, $uuid)
    {
        
    }

}