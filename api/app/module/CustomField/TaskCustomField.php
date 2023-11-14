<?php
namespace app\module\CustomField;

use app\common\exception\ResourceNotFoundException;
use app\module\BaseModule;
use app\model\TaskCustomFieldVal;

class TaskCustomField extends BaseModule
{

    public function setVal($kanbanId, $taskId, $fieldId, $val)
    {
        if (!$this->getCustomFieldModule()->fieldExist($fieldId)) {
            throw new ResourceNotFoundException('custom_fields.error.field_not_found');
        }
        $fields = [
            'kanban_id' => $kanbanId,
            'task_id' => $taskId,
            'val' => $val, 
            'created_time' => time(), 
            'updated_time' => time()
        ];

        if (TaskCustomFieldVal::where('field_id', $fieldId)->where('task_id', $taskId)->exists()) {
            return TaskCustomFieldVal::where('field_id', $fieldId)
                ->where('task_id', $taskId)
                ->update($fields);
        }
        $fields['field_id'] = $fieldId;

        return TaskCustomFieldVal::insertGetId($fields);
    }

    public function delByFieldId($fieldId)
    {
        return TaskCustomFieldVal::where('field_id', $fieldId)
            ->delete();
    }

    public function delSpecifiedFieldVal($fieldId, $val)
    {
        return TaskCustomFieldVal::where('field_id', $fieldId)
            ->where('val', $val)
            ->delete();
    }

    public function getKanbanCustomFieldsVal($kanbanId, $fields = ['kanban_id', 'task_id', 'field_id', 'val'])
    {
        return TaskCustomFieldVal::where('kanban_id', $kanbanId)->get($fields);
    }

    public function getTaskCustomFieldsVal($taskId, $fields = ['kanban_id', 'task_id', 'field_id', 'val'])
    {
        return TaskCustomFieldVal::where('task_id', $taskId)->get($fields);
    }

    public function getTaskCustomFieldVal($taskId, $fieldId, $fields = ['kanban_id', 'task_id', 'val'])
    {
        return TaskCustomFieldVal::where('task_id', $taskId)->where('field_id', $fieldId)->first($fields);
    }

}
