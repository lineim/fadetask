<?php
namespace app\controller;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use support\Request;

class TaskLabel extends Base
{


    public function get(Request $request, $labelId)
    {
        $label = $this->getKanbanLabelModule()->getLabel($labelId);
        return $this->json($label);
    }

    public function list(Request $request, $kanbanId)
    {
        $user = $this->getUser($request);
        if (!$this->getKanbanModule()->isMember($kanbanId, $user['id'])) {
            throw new AccessDeniedException('Access Denied!');
        }
        $labels = $this->getKanbanLabelModule()->getKanbanLabels($kanbanId);
        return $this->json($labels);
    }

    public function add(Request $request, $taskId, $labelId)
    {
        $user = $this->getUser($request);
        if (!$this->isKanbanMemberByTaskId($taskId, $user['id'])) {
            throw new AccessDeniedException("Access Denied!");
        }
        $created = $this->getKanbanLabelModule()->addTaskLabel($taskId, $labelId);

        if ($created) {
            $taskLabels = $this->getKanbanLabelModule()->getTaskLabels($taskId);
            return $this->json($taskLabels);
        }
        return $this->json([], 500);
    }

    public function remove(Request $request, $taskId, $labelId)
    {
        $user = $this->getUser($request);
        if (!$this->isKanbanMemberByTaskId($taskId, $user['id'])) {
            throw new AccessDeniedException("Access Denied!");
        }
        $exist = $this->getKanbanLabelModule()->taskHasLabel($taskId, $labelId);
        if (!$exist) { // 不存在，则认为已移除
            return $this->json(['success']);
        }
        $rm = $this->getKanbanLabelModule()->rmByTaskIdAndLabelId($taskId, $labelId);
        if ($rm) {
            return $this->json(['success']);
        }
        return $this->json(['failed'], 500);
    }

    public function edit(Request $request, $id)
    {
        $user = $this->getUser($request);
        $label = $this->getKanbanLabelModule()->getLabel($id, ['kanban_id']);
        if (!$label) {
            throw new BusinessException('Label Not Found!');
        }
        if (!$this->getKanbanModule()->isMember($label->kanban_id, $user['id'])) {
            throw new AccessDeniedException();
        }
        $name = $request->post('name', '');
        $updated = $this->getKanbanLabelModule()->updateName($id, $name);

        if ($updated) {
            return $this->json(['success']);
        }
        return $this->json(['failed'], 500);
    }

}