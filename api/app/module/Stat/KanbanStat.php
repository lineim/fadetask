<?php
namespace app\module\Stat;

use app\model\KanbanCfdData;
use app\module\BaseModule;
use app\model\Kanban as KanbanModel;
use app\model\KanbanTask as KanbanTaskModel;
use app\module\Kanban;
use app\module\KanbanTask;
use support\Db;

class KanbanStat extends BaseModule
{

    const TASK_COUNT_PER_LIST = 'stats.task_count.per_list';
    const TASK_COUNT_PER_MEMBER = 'stats.task_count.per_member';
    const TASK_COUNT_PER_LABEL = 'stats.task_count.per_label';
    const TASK_COUNT_DUE_DATE = 'stats.task_count.due_date';
    const KANBAN_CFD_DATA = 'stats.kanban.cfd_data';

    private $tasks = [];

    public function dashboard(int $kanbanId) : array
    {
        $this->initTasks($kanbanId);
        $statsData = [];

        $statsData[str_replace('.', '_', self::TASK_COUNT_PER_LIST)] = $this->perListTaskCount($kanbanId);
        $statsData[str_replace('.', '_', self::TASK_COUNT_PER_MEMBER)] = $this->perMemberTaskCount($kanbanId);
        $statsData[str_replace('.', '_', self::TASK_COUNT_PER_LABEL)] = $this->perLabelTaskCount($kanbanId);
        $statsData[str_replace('.', '_', self::TASK_COUNT_DUE_DATE)] = $this->dueDateTaskCount($kanbanId);
        $statsData[str_replace('.', '_', self::KANBAN_CFD_DATA)] = $this->getKanbanCfdData($kanbanId);
        return $statsData;
    }

    private function initTasks(int $kanbanId) : void
    {
        $this->tasks = $this->getKanbanTaskModule()->getKanbanTasks($kanbanId, ['id', 'is_finished', 'end_time']); // 为了减少tasks的查询次数，使用时应考虑内存泄露.
    }


    public function perMemberTaskCount(int $kanbanId) : array
    {
        $taskIds = $this->tasks->pluck('id')->toArray();

        $hasMemberTaskIds = [];
        $users = $this->getKanbanMemberModule()->getMembersWithUserInfo($kanbanId, ['id', 'name']);
        $usersTaskIds = [];
        $taskUserRelationships = $this->getTaskMemberModule()->getTasksMembers($taskIds, ['member_id', 'task_id']);
        foreach ($taskUserRelationships as $relationship) {
            if (!in_array($relationship->task_id, $hasMemberTaskIds)) {
                $hasMemberTaskIds[] = $relationship->task_id;
            }
            if (!isset($usersTaskIds[$relationship->member_id])) {
                $usersTaskIds[$relationship->member_id] = [];
            }
            if (!in_array($relationship->task_id, $usersTaskIds[$relationship->member_id])) {
                $usersTaskIds[$relationship->member_id][] = $relationship->task_id;
            }
        }

        $labels = [];
        $data = [];
        foreach ($users as $user) {
            $labels[] = $user['name'];
            $count = 0;
            if (isset($usersTaskIds[$user['id']])) {
                $count = count($usersTaskIds[$user['id']]);
            }
            $data[] = $count;
        }
        $labels[] = 'stats.task_count.no_member';
        $noMemberCount = count($taskIds) - count($hasMemberTaskIds);
        $data[] = max($noMemberCount, 0);

        return [
            'label_name' => self::TASK_COUNT_PER_MEMBER,
            'labels' => $labels,
            'data' => $data
        ];
    }

    public function perLabelTaskCount(int $kanbanId) : array
    {
        $tasks = $this->tasks;
        $labels = $this->getKanbanLabelModule()->getKanbanLabels($kanbanId, ['id', 'name', 'color']);
        $kanbanTaskLabels = $this->getKanbanLabelModule()->getKanbanTaskLabels($kanbanId, ['task_id', 'label_id']);

        $labelTaskIds = [];
        $hasLabelTaskIds = [];
        foreach ($kanbanTaskLabels as $kanbanTaskLabel) {
            if (!in_array($kanbanTaskLabel->task_id, $hasLabelTaskIds)) {
                $hasLabelTaskIds[] = $kanbanTaskLabel->task_id;
            }
            if (!isset($labelTaskIds[$kanbanTaskLabel->label_id])) {
                $labelTaskIds[$kanbanTaskLabel->label_id] = [];
            }
            if (!in_array($kanbanTaskLabel->task_id, $labelTaskIds[$kanbanTaskLabel->label_id])) {
                $labelTaskIds[$kanbanTaskLabel->label_id][] = $kanbanTaskLabel->task_id;
            }
        }

        $labelNames = [];
        $data = [];
        $labelBackgroundColors = [];
        foreach ($labels as $label) {
            $labelNames[] = $label->name;
            $labelBackgroundColors[] = $label->color;
            $labelTaskCount = isset($labelTaskIds[$label->id]) ? count($labelTaskIds[$label->id]) : 0;
            $data[] = $labelTaskCount;
        }

        $labelNames[] = 'stats.task_count.no_label';
        $noLabelCount = count($tasks) - count($hasLabelTaskIds);
        $data[] = max($noLabelCount, 0);

        return [
            'label_name' => self::TASK_COUNT_PER_LABEL,
            'labels' => $labelNames,
            'label_colors' => $labelBackgroundColors,
            'data' => $data
        ];
    }

    public function dueDateTaskCount(int $kanbanId) : array
    {
        $tasks = $this->tasks;
        $labels = ['stats.task_count.done', 'stats.task_count.due_soon', 'stats.task_count.due_later', 'stats.task_count.over_due', 'stats.task_count.no_due_date'];

        $overDueCount = 0;
        $soonDueCount = 0;
        $doneCount = 0;
        $laterDueCount = 0;
        $noDueDate = 0;
        foreach ($tasks as $task) {
            if ($task->is_finished) {
                $doneCount ++;
                continue;
            }
            if ($task->end_time <= 0) {
                $noDueDate ++;
                continue;
            }
            if ($task->end_time < time()) {
                $overDueCount ++;
            }
            if ($task->end_time > time() && $task->end_time < time() + 86400) {
                $soonDueCount ++;
            }
            if ($task->end_time > time() + 86400) {
                $laterDueCount ++;
            }
        }
        return [
            'label_name' => self::TASK_COUNT_DUE_DATE,
            'labels' => $labels,
            'data' => [$doneCount, $soonDueCount, $laterDueCount, $overDueCount, $noDueDate]
        ];
    }

    public function perListTaskCount(int $kanbanId) : array
    {
        $lists = $this->getKanbanModule()->getKanbanList($kanbanId);
        $countPerList = [
            'label_name' => self::TASK_COUNT_PER_LIST,
            'labels' => [],
            'data' => [],
        ];
        foreach ($lists as $list) {
            $countPerList['labels'][] = $list->name;
            $countPerList['data'][] = $list->task_count;
        }
        return $countPerList;
    }

    public function getKanbanCfdData(int $kanbanId) : array
    {
        // 只展示看板中有效的list，被删除的不展示
        $lists = $this->getKanbanModule()->getKanbanList($kanbanId);
        $listIds = $lists->pluck('list_id');

        $data = KanbanCfdData::where('kanban_id', $kanbanId)
            ->whereIn('list_id', $lists->pluck('id'))
            ->orderBy('id', 'ASC')
            ->get(['list_id', 'daytime', 'task_count']);
        $labels = [];
        $listData = [];
        $datasets = [];
        foreach ($data as $d) {
            if (date('Y', $d->daytime) == date('Y')) { // 当年
                $day = date('m/d', $d->daytime);
            } else {
                $day = date('y/m/d', $d->daytime);
            }
            if (!in_array($day, $labels)) {
                $labels[] = $day;
            }

            if (!isset($listData[$d->list_id])) {
                $listData[$d->list_id] = [];
            }
            $listData[$d->list_id][] = $d->task_count;
        }
        foreach ($lists as $list) {
            $datasets[] = [
                'label' => $list->name,
                'data' => $listData[$list->id] ?? [],
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    public function cfdStats(int $kanbanId) : bool 
    {
        $lists = $this->getKanbanModule()->getKanbanList($kanbanId);
        $tasks = $this->getKanbanTaskModule()->getKanbanTasks($kanbanId, ['id', 'list_id', 'is_delete', 'archived']);

        // 统计的数据算做昨天的数据
        $daytime = strtotime('-1 days');
        $daytime = strtotime(date('Y-m-d', $daytime));
        $time = time();

        $cfdData = [];
        $listTaskCount = [];
        
        // calculate list task count
        foreach ($tasks as $t) {
            if ($t->is_delete || $t->archived) {
                continue;
            }
            $listId = $t->list_id;
            if (!isset($listTaskCount[$listId])) {
                $listTaskCount[$listId] = 0; 
            }
            $listTaskCount[$listId] ++;
        }
        
        foreach ($lists as $list) {
            if ($list->archived) {
                continue;
            }
            $listId = $list->id;
            $cfdData[] = [
                'kanban_id' => $kanbanId,
                'list_id' => $listId,
                'daytime' => $daytime,
                'task_count' => $listTaskCount[$listId] ?? 0,
                'created_time' => $time
            ];
        }
        KanbanCfdData::insert($cfdData);
        return true;
    }

    public function getKanbansProgress(array $kanbanIds)
    {
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
                $kanbanModel->getTable() . '.id', 
                $kanbanModel->getTable() . '.name AS kanban_name', 
                Db::raw('COUNT(1) AS total'), 
                $taskModel->getTable().'.is_finished AS finished'
            )
            ->groupBy($kanbanModel->getTable() . '.id', $taskModel->getTable().'.is_finished')
            ->get();
        $kanbanProgress = [];
        foreach ($data as $item) {
            if (!isset($kanbanProgress[$item->id])) {
                $kanbanProgress[$item->id]['finished'] = 0;
                $kanbanProgress[$item->id]['unfinished'] = 0;
            }
            if ($item->finished) {
                $kanbanProgress[$item->id]['finished'] += $item->total;
            } else {
                $kanbanProgress[$item->id]['unfinished'] += $item->total;
            }
        }
        $kanbans = $this->getKanbanModule()->getByIds($kanbanIds, ['id', 'uuid', 'name']);

        foreach ($kanbans as &$k) {
            $k->finished = 0;
            $k->unfinished = 0;
            if (isset($kanbanProgress[$k->id]['finished'])) {
                $k->finished += $kanbanProgress[$k->id]['finished'];
            }
            if (isset($kanbanProgress[$k->id]['unfinished'])) {
                $k->unfinished += $kanbanProgress[$k->id]['unfinished'];
            }
            $k->total = $k->finished  + $k->unfinished;
        }
        return $kanbans;
    }

    public function kanbansOverdue(array $kanbanIds)
    {
        if (!$kanbanIds) {
            return [];
        }
        $kanbanModel = new KanbanModel();
        $taskModel = new KanbanTaskModel();

        $data = $kanbanModel->leftJoin($taskModel->getTable(), $kanbanModel->getTable().'.id', '=', $taskModel->getTable().'.kanban_id')
            ->whereIn($kanbanModel->getTable().'.id', $kanbanIds)
            ->where($kanbanModel->getTable().'.is_closed', Kanban::KANBAN_NOT_CLOSED)
            ->select(
                $kanbanModel->getTable() . '.id', 
                $kanbanModel->getTable() . '.uuid', 
                $kanbanModel->getTable() . '.name AS kanban_name', 
                $taskModel->getTable().'.id AS task_id',
                $taskModel->getTable().'.is_finished AS finished',
                $taskModel->getTable().'.archived AS task_archived',
                $taskModel->getTable().'.is_delete AS task_is_delete',
                $taskModel->getTable().'.end_time',
            )
            ->get();
        $kanbanOverdue = [];
        foreach ($data as $d) {
            if (!isset($kanbanOverdue[$d->id])) {
                $kanbanOverdue[$d->id]['id'] = $d->id;
                $kanbanOverdue[$d->id]['uuid'] = $d->uuid;
                $kanbanOverdue[$d->id]['name'] = $d->kanban_name;
                $kanbanOverdue[$d->id]['total'] = 0;
                $kanbanOverdue[$d->id]['overdue'] = 0;
                $kanbanOverdue[$d->id]['done'] = 0;
            } 
            if (!$d->task_id || ($d->task_id && $d->task_archived) || ($d->task_id && $d->task_is_delete)) { // 处理左连查询为null的情况
                continue;
            }
            $kanbanOverdue[$d->id]['total'] ++;
            if ($d->end_time < time() && !$d->finished) {
                $kanbanOverdue[$d->id]['overdue'] ++;
            }
            if ($d->finished) {
                $kanbanOverdue[$d->id]['done'] ++;
            }
        }
        $returnData = [];
        foreach ($kanbanOverdue as $data) {
            $returnData[] = $data;
        }
        return $returnData;
    }

}