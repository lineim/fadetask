<?php
namespace app\controller;

use support\Request;

class Sprint extends Base
{

    public function list(Request $request, $projectUuid)
    {
        $fields = ['title', 'start_time', 'end_time', 'status', 'total_point', 'finished_point'];
        $sprints = $this->getSprintModule()
            ->getProjectSprintsByProjectUuid($projectUuid, $fields);
        
        return $this->json($sprints);
    }

    public function add(Request $request)
    {
        $user = $this->getUser($request);
        $projectUuid  = $request->post('projectUuid', '');
        $sprint = [
            'title' => $request->post('name', ''),
            'end_time' => $request->post('endTime', 0),
            'start_time' => $request->post('startTime', 0)
        ];
        
        $newSprint = $this->getSprintModule()->createSprint($projectUuid, $user['id'], $sprint);
        unset($newSprint['id']);
        return $this->json($newSprint);
    }

}
