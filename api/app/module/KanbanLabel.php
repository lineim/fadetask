<?php
namespace app\module;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\common\exception\ResourceNotFoundException;
use app\model\KanbanLabel as LabelModel;
use app\model\KanbanTaskLabel as TaskLabelModel;
use Illuminate\Database\Eloquent\Collection;
use support\Db;
class KanbanLabel extends BaseModule
{

    const COLOR_RED = '#f54a45';
    const COLOR_YELLOW = '#f80';
    const COLOR_ORANGE = '#ffc60a';
    const COLOR_GREEN_LIGHT = '#b3d600';
    const COLOR_GREEN = '#2ea121';
    const COLOR_GREEN_BLUE = '#04b49c';
    const COLOR_BLUE_LIGHT = '#049fd7';
    const COLOR_BLUE = '#1890ff';
    const COLOR_PINK = '#f14ba9';
    const COLOR_PURPLE = '#7f3bf5';
    const COLOR_GRAY = '#646a73';

    public function getDefaultColors()
    {
        return [
            self::COLOR_RED,
            self::COLOR_YELLOW,
            self::COLOR_ORANGE,
            self::COLOR_GREEN_LIGHT,
            self::COLOR_GREEN,
            self::COLOR_GREEN_BLUE,
            self::COLOR_BLUE_LIGHT,
            self::COLOR_BLUE,
            self::COLOR_PINK,
            self::COLOR_PURPLE,
            self::COLOR_GRAY,
        ];
    }

    public function kanbanInit(int $kanbanId, int $userId)
    {
        if (LabelModel::where('kanban_id', $kanbanId)->exists()) {
            return true;
        }
        $labels = [];
        $colors = $this->getDefaultColors();
        $sort = 0;
        foreach ($colors as $color) {
            $labels[] = [
                'kanban_id' => $kanbanId,
                'name' => '',
                'sort' => $sort,
                'color' => $color,
                'creator_id' => $userId,
                'created_time' => time()
            ];
            $sort ++;
        }
        return LabelModel::insert($labels);
    }

    public function newLabel($kanbanId, $name, $color, $userId)
    {
        if (!$this->getKanbanModule()->isMember($kanbanId, $userId)) {
            throw new AccessDeniedException();
        }

        if (empty(trim($name))) { // 不允许增加多个name为空的同色label
            $exist = LabelModel::where('kanban_id', $kanbanId)
                ->where('name', $name)
                ->where('color', $color)
                ->first(['*']);
            if ($exist) {
                return false;
            }
        }

        $name = mb_substr($name, 0, 8);
        $maxSort = $this->getLabelMaxSort($kanbanId) ?? 0;
        $label = [
            'name' => $name,
            'color' => $color,
            'sort' => $maxSort + 1,
            'kanban_id' => $kanbanId,
            'creator_id' => $userId,
            'created_time' => time()
        ];
        $db = Db::connection('write');
        $db->beginTransaction();
        try {
            $id = LabelModel::insertGetId($label);
            Db::commit();
            return $this->getLabel($id);
        } catch (\Exception $e) {
            Db::rollBack();
            $this->getLogger()->error(sprintf("add label error with message: %s", $e->getMessage()), $e->getTrace());
            return false;
        }
    }

    public function getLabelMaxSort($kanbanId)
    {
        return LabelModel::where('kanban_id', $kanbanId)->max('sort');
    }

    public function addFromArray(array $label)
    {
        return LabelModel::insertGetId($label);
    }

    public function getLabel(int $id, array $fields = ['*'])
    {
        return LabelModel::where('id', $id)->first($fields);
    }

    public function updateLabel(int $id, string $name, string $color, $userId)
    {
        $label = $this->getLabel($id);
        if (!$label) {
            throw new ResourceNotFoundException('label not found');
        }
        if (!$this->getKanbanModule()->isMember($label->kanban_id, $userId)) {
            throw new AccessDeniedException('Access Denied!');
        }
        LabelModel::where('id', $id)->update(['name' => $name, 'color' => $color]);
        $label->name = $name;
        $label->color = $color;
        return $label;
    }

    /**
     * 按照ids中的顺序对label进行排序，index 越低的sort越高、排前面。
     */
    public function sortLabels(int $kanbanId, array $ids, int $userId)
    {
        if (empty($ids)) {
            return 0;
        }
        if (!$this->getKanbanModule()->isMember($kanbanId, $userId)) {
            throw new AccessDeniedException();
        }

        $ids = array_reverse($ids);

        $model = new LabelModel();
        return $model->sort($kanbanId, $ids);
    }

    public function deleteLabel(int $id, int $userId)
    {
        $label = $this->getLabel($id);
        if (!$label) {
            throw new ResourceNotFoundException('label not found');
        }
        if (!$this->getKanbanModule()->isMember($label->kanban_id, $userId)) {
            throw new AccessDeniedException('Access Denied!');
        }
        $deleteTaskLabel = TaskLabelModel::where('label_id', $id)->delete();
        if ($deleteTaskLabel === false) {
            return false;
        }
        LabelModel::where('id', $id)->delete();
        return true;
    }

    public function getKanbanLabels(int $kanbanId, array $fields = ['*']) : Collection
    {
        return LabelModel::where('kanban_id', $kanbanId)
            ->orderBy('sort', 'DESC')
            ->get($fields);
    }

    public function getTaskLabels(int $taskId, array $fields = ['*']) : Collection
    {
        $taskLabels = TaskLabelModel::where('task_id', $taskId)->get(['label_id']);
        if (!$taskLabels) {
            return new Collection([]);
        }
        $labelIds = $taskLabels->pluck('label_id');
        return LabelModel::whereIn('id', $labelIds->all())->orderBy('id', 'ASC')->get($fields);
    }

    public function getTaskLabelByTaskIdAndLabelId(int $taskId, int $labelId, array $fields = ['*']) : Collection
    {
        return TaskLabelModel::where('task_id', $taskId)
            ->where('label_id', $labelId)
            ->first($fields);
    }

    public function taskHasLabel(int $taskId, int $labelId) : bool
    {
        return TaskLabelModel::where('task_id', $taskId)
            ->where('label_id', $labelId)
            ->exists();
    }

    public function addTaskLabel(int $taskId, int $labelId)
    {
        $task = $this->getKanbanTaskModule()->getTask($taskId, ['kanban_id']);
        if (!$task) {
            throw new BusinessException("task not found");
        }
        $label = LabelModel::where('id', $labelId)->first(['id', 'kanban_id']);
        if (!$label) {
            throw new BusinessException("label not found");
        }
        if ($task->kanban_id != $label->kanban_id) {
            throw new AccessDeniedException("label and task not belong the same kanban");
        }
        if ($this->taskHasLabel($taskId, $labelId)) {
            return true;
        }
        $taskLabel = new TaskLabelModel();
        $taskLabel->task_id = $taskId;
        $taskLabel->label_id = $labelId;
        $taskLabel->kanban_id = $task->kanban_id;
        $taskLabel->created_time = time();

        $added = $taskLabel->save();
        $this->getKanbanTaskModule()->waveTaskLabelCount($taskId, 1);

        return $added;
    }

    /**
     * 替换task中的label id.
     * 
     * @return bool
     */
    public function replaceTaskLabel(int $taskId, $oldLabelId, $newLabelId, $newKanbanId) : bool
    {
        return TaskLabelModel::where('task_id', $taskId)
            ->where('label_id', $oldLabelId)
            ->update(['label_id' => $newLabelId, 'kanban_id' => $newKanbanId]);
    }

    public function rmByTaskIdAndLabelId(int $taskId, int $labelId)
    {
        $del = TaskLabelModel::where('task_id', $taskId)
            ->where('label_id', $labelId)
            ->delete();
        if ($del) {
            $this->getKanbanTaskModule()->waveTaskLabelCount($taskId, -1);
        }
        return $del;
    }

    public function rmByTaskIdAndLabelIds(int $taskId, array $labelIds)
    {
        $del = TaskLabelModel::where('task_id', $taskId)
            ->whereIn('label_id', $labelIds)
            ->delete();
        if ($del) {
            $this->getKanbanTaskModule()->waveTaskLabelCount($taskId, 0 - count($labelIds));
        }
        return $del;
    }

    public function getTasksLabelsGroupByTaskId(array $taskIds)
    {
        $relations = TaskLabelModel::whereIn('task_id', $taskIds)->get(['task_id', 'label_id']);
        if (!$relations) {
            return [];
        }
        $labelIds = $relations->pluck('label_id');
        $labels = LabelModel::whereIn('id', $labelIds)->get();

        $grouped = [];
        foreach ($relations as $r) {
            if (!isset($grouped[$r->task_id])) {
                $grouped[$r->task_id] = [];
            }
            $label = $labels->where('id', $r->label_id)->first();
            if ($label) {
                $grouped[$r->task_id][] = $label;
            }
        }
        return $grouped;
    }

    /**
     * @param integer $kanbanId 看板id.
     * @param array   $labelIds 标签id.
     * 
     * 
     * @return array taskIds 任务id.
     */
    public function getTaskIdsByKanbanIdAndLabelIds($kanbanId, array $labelIds)
    {
        $taskLabelRelations = TaskLabelModel::where('kanban_id', $kanbanId)->whereIn('label_id', $labelIds)->get(['task_id']);

        return $taskLabelRelations->pluck('task_id')->toArray();
    }

    public function getKanbanTaskLabels($kanbanId, array $fields = ['*'])
    {
        return TaskLabelModel::where('kanban_id', $kanbanId)->get($fields);
    }


    public function getTaskLabel(int $id, $fields = ['*'])
    {
        return TaskLabelModel::where('id', $id)->first($fields);
    }

    public function countTaskLabel(int $taskId) : int
    {
        return TaskLabelModel::where('task_id', $taskId)->count();
    }

}
