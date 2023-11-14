<?php
/**
 * This file is part of fade task project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\controller;

use support\Request;

class Me extends Base
{

    public function get(Request $request)
    {
        $user = $this->getUser($request);
        $user = $this->getUserModule()->getByUserId($user['id']);
        unset($user['passhash']);
        return $this->json($user);
    }

    public function update(Request $request)
    {
        $user = $this->getUser($request);
        $post = $request->post();

        $this->getUserModule()->updateUser($user['id'], $post);

        return $this->json($this->getUserModule()->getByUserId($user['id']));
    }


    public function updatePass(Request $request)
    {
        $user = $this->getUser($request);
        $oldPass = $request->post('old_pass');
        $newPass = $request->post('new_pass');        

        $update = $this->getUserModule()->updatePasswordByOldPassword($user['uuid'], $oldPass, $newPass);

        if ($update) {
            $token = $request->header('X-Auth-Token');
            $session = $request->session();
            $session->delete($token);

            return $this->json(true);
        }
        return $this->json(false);
    }

    public function todo(Request $request)
    {
        $me = $this->getUser($request);
        $sorts = [
            ['end_time', 'ASC'],
            ['priority', 'ASC']
        ];
        $page = $request->get('page', 1);
        $pageSize = $request->get('page_size', 10);
        $offset = ($page - 1) * $pageSize;
        $tasks = $this->getKanbanTaskModule()->getUserUndoneTasks($me['id'], $sorts, $offset, 9000);
        $kanbanIds = array_unique($tasks->pluck('kanban_id')->toArray());
        $listIds = array_unique($tasks->pluck('list_id')->toArray());
        $kanbansTmp = $this->getKanbanModule()->getByIds($kanbanIds, ['id', 'uuid', 'name']);
        
        $kanbans = [];
        foreach($kanbansTmp as $kanban) {
            $kanbans[$kanban->id] = $kanban;
        }
        $projects = $this->getProjectModule()->getKanbansProjects($kanbanIds, ['uuid', 'id', 'name']);

        $lists = $this->getKanbanModule()->getListByIds($listIds, ['id', 'name'])->pluck('name', 'id')->toArray();

        $total = $this->getKanbanTaskModule()->getUserUndoneTaskCount($me['id']);

        $now = time();
        $data = [
            'today' => [],
            'overdue' => [],
            'next' => [],
            'unscheduled' => [],
        ];
        $todayStartime = strtotime(date('Y-m-d 00:00:00'));
        $todayEndtime = strtotime(date('Y-m-d 23:59:59'));
        foreach ($tasks as &$task) {
            $kanban = $kanbans[$task->kanban_id] ?? [];
            $project = $projects[$task->kanban_id] ?? [];
            if ($project) {
                $task->project = $project->name;
                $task->project_uuid = $project->uuid;
            } else {
                $task->project = '';
                $task->project_uuid = 0;
            }
            $list = $lists[$task->list_id] ?? [];
            $task->list = $list;
            unset($kanban->id);
            unset($task->kanba_id);
            $task->kanban = $kanban;

            // Todo: Add Project
            $due_date = '';
            if ($task->end_time > 0) {
                $due_date = date('Y-m-d H:i:s', $task->end_time);
                $task->due_date = $due_date;
                if ($task->end_time >= $todayStartime && $task->end_time <= $todayEndtime) {
                    $data['today'][] = $task;
                } elseif ($task->end_time < $todayStartime) {
                    $data['overdue'][] = $task;
                } else {
                    $data['next'][] = $task;
                }
            } else {
                $data['unscheduled'][] = $task;
            }
           
            if ($task->end_time <= $now) {
                $task->expired = true;
            }
        }

        $return = [
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'data' => $data 
        ];
        
        return $this->json($return);
    }

    public function hasNotification(Request $request)
    {
        $me = $this->getUser($request);
        return $this->json($this->getNotificationModule()->hasUnReadNotifications($me['id']));
    }

    public function notifications(Request $request)
    {
        $me = $this->getUser($request);
        $type = $request->get('type', '-1');
        $start = $request->get('offset', 0);
        $limit = $request->get('limit', 10);
        

        $notifications = $this->getNotificationModule()
            ->getUserNotifications($me['id'], $type, $start, $limit);

        foreach ($notifications as &$n) {
            $n->params = json_decode($n->params, true);
            $n->created_date = date('Y-m-d H:i:s', $n->created_time);
        }

        return $this->json([
            'notifications' => $notifications,
            'has_unread' => $this->getNotificationModule()->hasUnReadNotifications($me['id'])
        ]);
    }

    public function notificationReaded(Request $request)
    {
        $me = $this->getUser($request);

        $this->getNotificationModule()->batchReadedByUserId($me['id']);

        return $this->json(true);
    }

    public function favorites(Request $request)
    {
        $user = $this->getUser($request);

        $kanbans = $this->getKanbanModule()->getUserFavorites($user['id'], ['*']);
        return $this->json($kanbans);
    }

    public function project(Request $request)
    {
        $user = $this->getUser($request);
        $managerProject = $request->get('manager_project', 0);
        if ($managerProject) {
            $projects = $this->getProjectModule()->getUserManageProjects($user['id'], ['uuid', 'name']);
        } else {
            $projects = $this->getProjectModule()->getUserProjects($user['id'], ['uuid', 'name']);
        }
        return $this->json($projects);
    }

}
