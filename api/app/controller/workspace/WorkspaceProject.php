<?php
namespace app\controller\workspace;

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
        $users = $this->getUserModule()->getByUserIds($userIds, ['id', 'name', 'email']);
        $usersIndexed = [];
        foreach ($users as $user) {
            $usersIndexed[$user->id]= $user;
        }
        foreach ($projects->items() as &$project) {
            $project->creator = $usersIndexed[$project->user_id] ?? [];
        }
        return $this->json($projects);
    }

}