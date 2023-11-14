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
use app\common\exception\BusinessException;
use app\common\exception\ResourceNotFoundException;
use app\module\CustomField\CustomField as CustomFieldCustomField;
use support\Request;
use app\module\Kanban as KanbanModule;
use support\Response;

class CustomField extends Base
{

    public function add(Request $request, $kanbanId) : Response
    {
        $user = $this->getUser($request);
        $name = $request->post('name');
        $type = $request->post('type');
        $showOnFront = $request->post('show_front_card', 1);
        $options = $request->post('options');

        $kanban = $this->getKanbanModule()->getByUuid($kanbanId, ['id']);
        $kanbanId = $kanban->id;
        $id = $this->getCustomFieldModule()->create($kanbanId, $user['id'], $name, $type, $showOnFront);
        if (!$id) {
            throw new BusinessException('system_error');
        }
        if ($type == CustomFieldCustomField::SUPPORT_TYPE_DROPDOWN) {
            $this->getCustomFieldModule()->batchAddDropdownFieldOptions($id, $user['id'], $options);
        }
        $field = $this->getCustomFieldModule()->getField($id);
        return $this->json($field);
    }

    public function edit(Request $request, $id) : Response
    {
        $user = $this->getUser($request);
        $data = $request->post();
        $update = $this->getCustomFieldModule()->update($id, $data, $user['id']);

        return $this->json($update);
    }

    public function get(Request $request, $kanbanId, $id)
    {

    }

    public function del(Request $request, $id) : Response
    {
        $user = $this->getUser($request);
        $field = $this->getCustomFieldModule()->getField($id, ['kanban_id']);
        $kanbanId = $field->kanban_id;
        if (!$this->getKanbanModule()->isAdmin($kanbanId, $user['id'])) {
            throw new AccessDeniedException();
        }
        $del = $this->getCustomFieldModule()->delCustomField($id);

        return $this->json($del);
    }

    public function addOption(Request $request, $fieldId) : Response
    {
        $user = $this->getUser($request);
        $val = $request->post('val', '');
        $id = $this->getCustomFieldModule()->addDropdownFieldOption($fieldId, $user['id'], $val);
        $opt = $this->getCustomFieldModule()->getDropdwonFieldOption($id, ['id', 'field_id', 'val']);
        return $this->json($opt);
    }

    public function setOption(Request $request, $optionId) : Response
    {
        $user = $this->getUser($request);
        $val = $request->post('val', '');
        $option = $this->getCustomFieldModule()->getDropdwonFieldOption($optionId, ['val', 'field_id']);
        if (!$option) {
            return $this->json(false);
        }
        if ($option->val == $val) {
            return $this->json(true);
        }
        $field = $this->getCustomFieldModule()->getField($option->field_id, ['kanban_id']);
        if (!$this->getKanbanModule()->isAdmin($field->kanban_id, $user['id'])) {
            throw new AccessDeniedException();
        }
        $update = $this->getCustomFieldModule()->updateDropdownFieldVal($optionId, $val);
        return $this->json($update);
    }

    public function delOption(Request $request, $id) : Response
    {
        $user = $this->getUser($request);
        $option = $this->getCustomFieldModule()->getDropdwonFieldOption($id, ['val', 'field_id']);
        if (!$option) {
            return $this->json(false);
        }
       
        $field = $this->getCustomFieldModule()->getField($option->field_id, ['kanban_id']);
        if (!$this->getKanbanModule()->isAdmin($field->kanban_id, $user['id'])) {
            throw new AccessDeniedException();
        }
        $delete = $this->getCustomFieldModule()->delDropdownFieldVal($id);
        return $this->json($delete);
    }

    public function setFieldVal(Request $request, $taskId, $id) : Response
    {
        $user = $this->getUser($request);
        $val = $request->post('val', '');

        $task = $this->getKanbanTaskModule()->getTask($taskId, ['id', 'kanban_id']);
        if (!$task) {
            throw new ResourceNotFoundException('task.not_found');
        }
        if (!$this->getKanbanModule()->isMember($task->kanban_id, $user['id'])) {
            throw new AccessDeniedException();
        }
        $set = $this->getTaskCustomeFieldModule()->setVal($task->kanban_id, $taskId, $id, $val);

        return $this->json($set);
    }

}
