<?php
/**
 * This file is part of fade task project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\module\Stat;

use app\model\KanbanTask as TaskModel;
use app\model\KanbanTaskMember as TaskMemberModel;
use app\model\User as UserModel;
use app\model\Kanban as KanbanModel;
use app\module\KanbanTask;
use app\module\User;
use app\module\Kanban;
use app\module\BaseModule;

class TaskStat extends BaseModule
{

    public function getMemberTasksStat($minTaskMemberId = 0, $start = 0, $limit = 10)
    {
        $model = new TaskMemberModel();
        $taskModel = new TaskModel();
        $userModel = new UserModel();
        $kanbanModel = new KanbanModel();

        return $model->join($taskModel->getTable(), $model->getTable().'.task_id', '=', $taskModel->getTable().'.id')
            ->join($userModel->getTable(), $model->getTable().'.member_id', '=', $userModel->getTable().'.id')
            ->join($kanbanModel->getTable(), $taskModel->getTable().'.kanban_id', '=', $kanbanModel->getTable().'.id')
            ->where($model->getTable().'.id', '>', $minTaskMemberId)
            ->where($taskModel->getTable().'.is_finished', KanbanTask::TASK_UNDONE)
            ->where($taskModel->getTable().'.is_delete', KanbanTask::TASK_NOT_DELETED)
            ->where($taskModel->getTable().'.archived', KanbanTask::TASK_NOT_ARCHIVED)
            ->where($userModel->getTable().'.verified', User::VERIFIED)
            ->where($kanbanModel->getTable().'.is_closed', Kanban::KANBAN_NOT_CLOSED)
            ->select($model->getTable().'.*', $taskModel->getTable().'.*', $userModel->getTable().'.email as email')
            ->orderBy($taskModel->getTable().'.end_time', 'ASC')
            ->offset($start)
            ->limit($limit)
            ->get();
    }

}
