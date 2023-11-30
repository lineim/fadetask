<?php
/**
 * This file is part of fade task project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\module\Stat;

use app\common\exception\BusinessException;
use app\model\ProjectKanban as ProjectKanbanModel;
use app\model\Kanban as KanbanModel;
use app\model\KanbanMember;
use app\model\KanbanTask as KanbanTaskModel;
use app\model\KanbanTaskMember;
use app\module\BaseModule;
use app\module\Kanban;
use app\module\KanbanTask;
use stdClass;
use support\Db;

class ProjectStat extends BaseModule
{
    protected $project;
    protected $kanbanIds = [];

    public function overview($uuid)
    {
        $this->initProject($uuid);
        $progress = $this->getKanbanProgress();

        return [
            'progress' => $progress,
            'main_indicators' => $this->mainIndicators(),
            'member_load' => $this->memberLoad(),
            'kanban_overdue' => $this->kanbanOverdue(),
            'priority_distribution' => $this->priorityDistribution()
        ];
    }

    protected function initProject($uuid)
    {
        $this->project = $this->getProjectModule()->getProjectByUuid($uuid, ['id']);
        if (!$this->project) {
            throw new BusinessException('project.not_found');
        }
        if ($this->project->is_closed) {
            throw new BusinessException('project.closed');
        }
        $kanbanIds = ProjectKanbanModel::where('project_id', $this->project->id)
            ->pluck('kanban_id')
            ->toArray();
        $kanbans = $this->getKanbanModule()->getByIds($kanbanIds, ['id']);
        $this->kanbanIds = $kanbans->pluck('id')->toArray();
    }

    protected function memberLoad()
    {
        if (!$this->kanbanIds) {
            return [];
        }
        $memberIds = array_unique(KanbanMember::whereIn('kanban_id', $this->kanbanIds)
            ->pluck('member_id')
            ->toArray());
        
        $taskModel = new KanbanTaskModel();
        $taskTable = $taskModel->getTable();
        $taskMemberModel = new KanbanTaskMember();    
        $taskMemberTable = $taskMemberModel->getTable();
        $tasks = $taskMemberModel->leftJoin($taskTable, $taskTable.'.id', '=', $taskMemberTable.'.task_id')
            ->whereIn($taskTable . '.kanban_id', $this->kanbanIds)
            ->where($taskTable . '.archived', KanbanTask::TASK_NOT_ARCHIVED)
            ->get([$taskMemberTable . '.member_id', $taskTable . '.is_finished', $taskTable . '.end_time']);
        
        $membersLoad = [];
        $defaultLoad = [
            'total' => 0,
            'done' => 0,
            'overdue' => 0
        ];
        foreach ($tasks as $t) {
            if (!isset($membersLoad[$t->member_id])) {
                $membersLoad[$t->member_id] = $defaultLoad;
            }
            $membersLoad[$t->member_id]['total'] ++;
            if ($t->is_finished) {
                $membersLoad[$t->member_id]['done'] ++;
            } elseif ($t->end_time < time()) {
                $membersLoad[$t->member_id]['overdue'] ++;
            }
        }
        $users = $this->getUserModule()->getByUserIds($memberIds, ['id', 'uuid', 'name']);
        foreach ($users as &$u) {
            if (isset($membersLoad[$u->id])) {
                $u->load = $membersLoad[$u->id];
            } else {
                $u->load = $defaultLoad;
            }
            unset($u->id);
        }

        $tasks = $taskModel->whereIn('kanban_id', $this->kanbanIds)
            ->where('archived', KanbanTask::TASK_NOT_ARCHIVED)
            ->where('member_count', 0)
            ->get(['is_finished', 'end_time']);
        $unAssign = $defaultLoad;
        foreach ($tasks as $t) {
            $unAssign['total'] ++;
            if ($t->is_finished) {
                $unAssign['done'] ++;
            } elseif ($t->end_time < time()) {
                $unAssign['overdue'] ++;
            }
        }
        $unAssignUser = new stdClass();
        $unAssignUser->name = '未分配用户';
        $unAssignUser->load = $unAssign;

        $users = $users->toArray();
        array_push($users, $unAssignUser);
        return $users;
    }

    /**
     * 计算主要指标
     */
    protected function mainIndicators()
    {
        $data = [
            'total' => 0,
            'done' => 0,
            'overdue' => 0,
            'today_overdue' => 0
        ];
        if (!$this->kanbanIds) {
            return $data;
        }
        $total = KanbanTaskModel::whereIn('kanban_id', $this->kanbanIds)
            ->where('archived', KanbanTask::TASK_NOT_ARCHIVED)
            ->where('is_delete', KanbanTask::TASK_NOT_DELETED)
            ->count();
        $done = KanbanTaskModel::whereIn('kanban_id', $this->kanbanIds)
            ->where('is_finished', KanbanTask::TASK_DONE)
            ->where('archived', KanbanTask::TASK_NOT_ARCHIVED)
            ->where('is_delete', KanbanTask::TASK_NOT_DELETED)
            ->count();
        $overdue = KanbanTaskModel::whereIn('kanban_id', $this->kanbanIds)
            ->where('is_finished', KanbanTask::TASK_UNDONE)
            ->whereBetween('end_time', [1, time()])
            ->where('archived', KanbanTask::TASK_NOT_ARCHIVED)
            ->where('is_delete', KanbanTask::TASK_NOT_DELETED)
            ->count();

        $startTime = strtotime(date('Y-m-d') . ' 00:00:00');
        $endTime = strtotime(date('Y-m-d') . ' 23:59:59');
        $todayOverdue = KanbanTaskModel::whereIn('kanban_id', $this->kanbanIds)
            ->where('is_finished', KanbanTask::TASK_UNDONE)
            ->whereBetween('end_time', [$startTime, $endTime])
            ->where('archived', KanbanTask::TASK_NOT_ARCHIVED)
            ->where('is_delete', KanbanTask::TASK_NOT_DELETED)
            ->count();
        return [
            'total' => $total,
            'done' => $done,
            'overdue' => $overdue,
            'today_overdue' => $todayOverdue
        ];    
    }

    protected function getKanbanProgress()
    {
        return $this->getKanbanStatModule()->getKanbansProgress($this->kanbanIds);
    }

    public function kanbanOverdue()
    {
        return $this->getKanbanStatModule()->kanbansOverdue($this->kanbanIds);
    }

    protected function priorityDistribution()
    {
        $kanbanIds = $this->kanbanIds;
        if (!$kanbanIds) {
            return [];
        }
        $kanbanModel = new KanbanModel();
        $taskModel = new KanbanTaskModel();

        $data = $kanbanModel->leftJoin($taskModel->getTable(), $kanbanModel->getTable().'.id', '=', $taskModel->getTable().'.kanban_id')
            ->whereIn($kanbanModel->getTable().'.id', $kanbanIds)
            ->where($kanbanModel->getTable().'.is_closed', Kanban::KANBAN_NOT_CLOSED)
            ->where($taskModel->getTable().'.archived', KanbanTask::TASK_NOT_ARCHIVED)
            ->where($taskModel->getTable().'.is_delete', KanbanTask::TASK_NOT_DELETED)
            ->select(
                Db::raw('COUNT(1) AS total'), 
                $taskModel->getTable().'.priority AS priority'
            )
            ->groupBy($taskModel->getTable() . '.priority')
            ->orderBy($taskModel->getTable().'.priority', 'asc')
            ->get();
        foreach ($data as &$item) {
            switch ($item->priority) {
                case 0:
                    $item->name = 'task.priority.emergency';
                    break;
                case 1:
                    $item->name = 'task.priority.hight';
                    break;
                case 2: 
                    $item->name = 'task.priority.normal';
                    break;
                case 3:
                    $item->name = 'task.priority.low';
                    break;
                default:
                    $item->name = 'task.priority.normal';
            }
        }
        return $data;
    }

    public function setKanbanIds(array $kanbanIds)
    {
        $this->kanbanIds = $kanbanIds;
    }

}
