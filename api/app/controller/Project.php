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
use support\Request;

class Project extends Base
{

    public function add(Request $request)
    {
        $user = $this->getUser($request);
        $data = $request->post();

        // $validator = new Validator($data);
        // $validator->rules([
        //     'required' => ['name'],
        //     'lengthMax' => [
        //         ['name', 60],
        //         ['desc', 256]
        //     ]
        // ]);
        // if (!$validator->validate()) {
        //     return $this->json([], 500, $validator->errors());
        // }
        $uuid = $this->getProjectModule()->createProject($data, $user['id']);
        return $this->json($uuid);
    }

    public function update(Request $request, $uuid)
    {
        $data = $request->post();
        if (isset($data['name']) && empty($data['name'])) {
            return $this->json([], 500, 'Name is required');
        }
        $project = \app\model\Project::where('uuid', $uuid)
            ->where('is_deleted', 0)
            ->first();
        if (!empty($data['name'])) {
            $project->name = $data['name'];
        }
        if (!empty($data['desc'])) {
            $project->description = $data['desc'];
        }

        if ($project->save()) {
            unset($project['id']);
            return $this->json($project);
        }
        return $this->json([], 500, 'Server Error');
    }

    public function close(Request $request, $uuid)
    {
        $user = $this->getUser($request);
        $closeProjectKanbans = $request->post('close_kanban', 0);
        $close = $this->getProjectModule()->close($uuid, $user['id'], $closeProjectKanbans);
        return $this->json($close);
    }

    public function open(Request $request, $uuid)
    {
        $user = $this->getUser($request);
        $open = $this->getProjectModule()->open($uuid, $user['id']);
        return $this->json($open);
    }

    public function get(Request $request, $uuid)
    {
        $user = $this->getUser($request);
        $isMember = $this->getProjectModule()->isMember($uuid, $user['id']);
        if (!$isMember) {
            throw new AccessDeniedException();
        }
        $isManager = $this->getProjectModule()->isManager($uuid, $user['id']);
        
        $project = $this->getProjectModule()->getProjectByUuid($uuid);
        if (!$project) {
            return $this->json([], 404, 'project.not_found');
        }
        if ($project->is_closed) {
            return $this->json([], 404, 'project.closed');
        }

        $project->created_date = date('Y-m-d H:i:s', $project->created_time);
        $project->creator = $this->getUserModule()->getByUserId($project->user_id, ['name']);
        $project->is_manager = $isManager;
        $this->getProjectModule()->viewProject($project['id'], $user['id']);
        unset($project['id']);
        
        return $this->json($project);
    }

    public function list(Request $request)
    {
        $user = $this->getUser($request);
        $page = $request->get('page', 0);
        $pageSize = $request->get('pageSize', 10);
        $closed = $request->get('closed', 0);
        $offset = ($page - 1) * $pageSize;
        $data = $this->getProjectModule()->getUserProject($user['id'], $closed, $offset, $pageSize);
        return $this->json($data);
    }

    public function search(Request $request, $uuid)
    {
        $user = $this->getUser($request);
        $page = $request->get('page', 0);
        $pageSize = $request->get('pageSize', 10);
        $offset = ($page - 1) * $pageSize;

        return $this->json([]);
    }

}
