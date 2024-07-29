<?php
namespace app\controller\workspace;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\controller\Base;
use support\Request;

class WorkspaceProject extends Base
{

    public function get(Request $request, $uuid, $spaceUuid)
    {
        $project = $this->getProjectModule()->getProjectByUuid($spaceUuid, ['uuid', 'name', 'description', 'color', 'is_public']);
        if (!$project) {
            throw new BusinessException('project.not_found');
        }
        return $this->json($project);
    }

    public function update(Request $request, $uuid, $spaceUuid)
    {
        $updata = $this->getProjectModule()->updateProject($spaceUuid, $request->all());
        
        return $this->json($updata);
    }
    
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
        $projectIds = [];
        foreach ($projects as $project) {
            $userIds[] = $project->user_id;
            $projectIds[] = $project->id;
        }
        $users = [];
        $usersIndexed = [];
        if ($userIds) {
            $users = $this->getUserModule()->getByUserIds($userIds, ['id', 'name', 'email']);
            foreach ($users as $user) {
                $usersIndexed[$user->id]= $user;
            }
        }

        $projectLists = $this->getKanbanModule()->getProjectsList($projectIds, ['name', 'asc'], ['uuid', 'name', 'color', 'project_id', 'created_time']);
        $listIndexByProjectId = [];
        foreach ($projectLists as $list) {
            $listIndexByProjectId[$list->project_id][] = $list;
        }

        foreach ($projects as &$project) {
            $project->list = $listIndexByProjectId[$project->id] ?? [];
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
        $name = $request->post('name', '');
        $desc = $request->post('desc', '');
        $isPublic = $request->post('is_public', 0);
        $color = $request->post('color', 'blue');

        $workspace = $this->getWorkspaceModule()->getByUuid($uuid, ['id']);
        if (!$workspace) {
            throw new BusinessException('workspace.not_found');
        }

        $project = ['name' => $name, 'desc' => $desc, 'color' => $color, 'is_public' => $isPublic, 'workspace_id' => $workspace->id];
        $project = $this->getProjectModule()->createProject($project, $this->getUser()['id']);
        return $this->json($project);
    }

}