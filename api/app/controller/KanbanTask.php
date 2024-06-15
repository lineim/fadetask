<?php
namespace app\controller;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\common\exception\InvalidParamsException;
use app\common\exception\ResourceNotFoundException;
use support\Request;
use app\common\toolkit\ArrayHelper;

class KanbanTask extends Base
{

    public function get(Request $request, $kanbanId, $id)
    {
        $user = $this->getUser($request);
        $task = $this->getKanbanTaskModule()->getTask($id);
        if (!$task) {
            throw new ResourceNotFoundException('task.not_found');
        }
        $kanbanId = $task->kanban_id;
        if (!$this->getKanbanModule()->isMember($kanbanId, $user['id'])) {
            throw new AccessDeniedException();
        }
        $list = $this->getKanbanModule()->getList($task->list_id, ['name']);
        $task->list = $list;
        $labels = $this->getKanbanLabelModule()->getTaskLabels($id, ['id', 'name', 'color']);
        $kanbanList = $this->getKanbanModule()->getKanbanList($task->kanban_id);
        $task->labels = $labels ? $labels : [];
        $kanbanLabels = $this->getKanbanLabelModule()->getKanbanLabels($task->kanban_id, ['id', 'name', 'color']);
        $task->kanban_labels = $kanbanLabels ? $kanbanLabels : [];
        $task->kanban_label_colors = $this->getKanbanLabelModule()->getDefaultColors();
        $task->kanban_list = $kanbanList;
        $task->customfields = $this->getCustomFieldModule()->getKanbanCustomFields($kanbanId, ['name', 'type', 'show_on_card_front', 'sort']);
        $customFieldValues = $this->getTaskCustomeFieldModule()->getTaskCustomFieldsVal($task->id);
        $task->customfield_vals = new \stdClass();
        $tmpVals = [];
        foreach ($customFieldValues as $fieldVal) {
            $tmpVals[$fieldVal->field_id] = $fieldVal->val;
        }
        if ($tmpVals) {
            $task->customfield_vals = $tmpVals;
        }
        $attachments = $this->getAttachmentModule()->getTaskAttachments($id, $user['id']);
        $task->attachments = $attachments ? $attachments : [];

        $fields = ['id', 'task_id', 'title', 'is_done', 'done_time'];
        $checklists = $this->getTaskCheckListModule()->getByTaskId($id, $user['id'], $fields);
        $task->check_list = $checklists ? $checklists : [];

        $members = $this->getTaskMemberModule()->getTaskMembersInfo($id, ['id', 'name', 'email', 'avatar']);
        foreach ($members as &$m) {
            $m->isMember = true;
        }
        $task->members = $members;
        $dueNotifyTimes = $this->getKanbanTaskModule()->getDueNotifyTimeTxt();
        $task->dueNotifyTimes = $dueNotifyTimes;
        $task->created_date = date('Y-m-d H:i:s', $task->created_time);
        $task->creator = $this->getUserModule()->getByUserId($task->create_user, ['name', 'uuid']);
        $kanban = $this->getKanbanModule()->get($task->kanban_id, ['name', 'uuid']);
        $project = $this->getProjectModule()->getProjectByKanbanId($task->kanban_id, ['name', 'uuid']);
        $kanban->project = $project;
        $task->kanban = $kanban;
        $task->subtask_count = $this->getKanbanTaskModule()->getSubtasksCount($task->id);

        return $this->json($task);
    }

    public function getSubtasksTree(Request $request, $kanbanId, $id)
    {
        $parentId = $id;
        $user = $this->getUser($request);
        $task = $this->getKanbanTaskModule()->getTask($id, ['id', 'uuid', 'title', 'parent_id']);
        if ($task->parent_id) {
            $parentId = $task->parent_id;
            $task = $this->getKanbanTaskModule()->getTask($parentId, ['id', 'uuid', 'title', 'parent_id']);
        }
        $subtasks = $this->getKanbanTaskModule()->getSubtasks($parentId, $user['id'], ['id', 'uuid', 'title']);
        
        $task->subtasks = $subtasks;

        return $this->json($task);
    }

    public function activity(Request $request, $kanbanId, $id)
    {
        $user = $this->getUser($request);
        $task = $this->getKanbanTaskModule()->getTask($id);
        if (!$task) {
            throw new ResourceNotFoundException('task.not_found');
        }
        $kanbanId = $task->kanban_id;
        if (!$this->getKanbanModule()->isMember($kanbanId, $user['id'])) {
            throw new AccessDeniedException();
        }

        return $this->json($this->getTaskLogModule()->getActivity($id, $user['id'], 0, 1000));
    }

    public function add(Request $request, $kanbanId)
    {
        $user = $this->getUser($request);

        $task = [
            'title' => $request->post('title', ''),
            'desc' => $request->post('desc', ''),
            'kanbanId' => $kanbanId,
            'parentId' => $request->post('parent_id', 0),
        ];

        $startDate = $request->post('start_date', '');
        $endDate = $request->post('end_date', '');
        $listId = $request->post('list_id', 0);
        if ($startDate) {
            $task['start_time'] = strtotime($startDate);
        }
        if ($endDate) {
            $task['end_time'] = strtotime($endDate);
        }

        $taskId = $this->getKanbanTaskModule()->createTask($task, $user['id'], $listId);
        $newTask = $this->getKanbanTaskModule()->getTask($taskId);

        return $this->json(['task' => $newTask]);
    }

    public function setTitle(Request $request, $kanbanId, $id)
    {
        $title = $request->post('title', '');
        $user = $this->getUser($request);
        $isSuccess = $this->getKanbanTaskModule()->setTitle($id, $title, $user['id']);

        return $this->json($isSuccess);
    }

    public function changeList(Request $request, $id)
    {
        $listId = $request->post('list_id', 0);
        $task = $this->getKanbanTaskModule()->getTask($id, ['kanban_id']);
        if (!$task) {
            throw new BusinessException('Task Not Found!');
        }
        $currentUser = $this->getUser($request);
        if (!$this->getKanbanModule()->isMember($task->kanban_id, $currentUser['id'])) {
            throw new AccessDeniedException();
        }
        $changed = $this->getKanbanTaskModule()->changeList($id, $listId, $currentUser['id']);
        return $this->json(['success' => $changed, 'task_finished' => $this->getKanbanModule()->isCompletedList($listId)]);
    }

    public function setMember(Request $request, $id)
    {
        $memberId =$request->post('member_id', '');
        if (!$memberId) {
            throw new InvalidParamsException("Member Id Required!");
        }
        $user = $this->getUser($request);

        $joined = $this->getKanbanTaskModule()->setMembers($id, [$memberId], $user['id']);

        if ($joined) {
            $member = $this->getUserModule()->getByUserId($memberId, ['id', 'email', 'avatar', 'name']);
            $member->isMember = true;
            return $this->json($member);
        }

        return $this->json($joined);
    }

    public function removeMember(Request $request, $id)
    {
        $memberId = $request->post('member_id', '');
        if (!$memberId) {
            throw new InvalidParamsException("Member Id Required!");
        }
        $user = $this->getUser($request);

        $removed = $this->getKanbanTaskModule()->removeMembers($id, [$memberId], $user['id']);

        return $this->json($removed);
    }

    public function setDate(Request $request, $kanbanId, $id)
    {
        $user = $this->getUser($request);
        $dates = [];
        $startDate = $request->post('start_date', '');
        $endDate = $request->post('end_date', '');
        $notifyTime = $request->post('due_notify_time', 0);
        $dates['start_date'] = $startDate;
        $dates['end_date'] = $endDate;
        
        $this->getKanbanTaskModule()->setDate($id, $dates, $notifyTime, $user['id']);
        $task = $this->getKanbanTaskModule()->getTask($id);
        
        return $this->json($task );
    }

    public function clearDueDate(Request $request, $kanbanId, $id)
    {
        $user = $this->getUser($request);
        $isSuccess = $this->getKanbanTaskModule()->clearDueDate($id, $user['id']);

        return $this->json($isSuccess);
    }

    public function setDesc(Request $request, $kanbanId, $id)
    {
        $desc = $request->post('desc', '');
        $user = $this->getUser($request);
        $isSuccess = $this->getKanbanTaskModule()->setDesc($id, $desc, $user['id']);

        return $this->json($isSuccess);
    }

    public function move(Request $request, $kanbanId, $taskId)
    {
        $data = $request->post();
        $cardListId = $data['cardListId'] ?? 0;
        $cardsSort = $data['cardsSort'] ?? [];
        
        $user = $this->getUser($request);

        $this->getKanbanTaskModule()->moveTaskInKanban($cardListId, $taskId, $user['id'], $cardsSort);

        $list = $this->getKanbanModule()->getList($cardListId, ['completed']);

        $list->completed ? $data['cardChanged'] = true : $data['cardChanged'] = false;

        return $this->json($data);
    }

    public function moveToOther(Request $request, $kanbanId, $taskId)
    {
        $user = $this->getUser($request);
        $toKanbanUUid = $request->post('to_board_id');
        $toListId = $request->post('to_list_id');

        $moved = $this->getKanbanTaskModule()->moveToOther($taskId, $toKanbanUUid, $toListId, $user['id']);
        $data['success'] = (bool) $moved;

        $list = $this->getKanbanModule()->getList($toListId, ['completed']);

        $list->completed ? $data['cardChanged'] = true : $data['cardChanged'] = false;

        return $this->json($data);
    }

    public function copy(Request $request, $kanbanId, $taskId)
    {
        $toListId = $request->post('to_list_id', 0);
        if (!$toListId) {
            return $this->json([], 1, '参数错误');
        }

        $user = $this->getUser($request);
        $result = $this->getKanbanTaskModule()->copyTaskInKanban($toListId, $taskId, $user['id']);
        if (!$result) {
            return $this->json([], 1, '复制失败');
        }

        return $this->json(['success' => true], 0, '复制成功');
    }

    public function copyToOther(Request $request, $kanbanId, $taskId)
    {
        $toKanbanUUid = $request->post('to_board_id', '');
        $toListId = $request->post('to_list_id', 0);
        if (!$toListId || !$toKanbanUUid) {
            return $this->json([], 1, '参数错误！');
        }
        $user = $this->getUser($request);
        $result = $this->getKanbanTaskModule()->copyTaskToOther($toKanbanUUid, $toListId, $taskId, $user['id']);
        if (!$result) {
            return $this->json([], 1, '复制失败！');
        }

        return $this->json(['success' => true], 0, '复制成功！');
    }

    public function archive(Request $request, $taskId)
    {
        $user = $this->getUser($request);
        if (!$this->getKanbanModule()->isKanbanMemberByTaskId($user['id'], $taskId)) {
            throw new AccessDeniedException('Access Denied!');
        }
        $archive = $this->getKanbanTaskModule()->archive($taskId, $user['id']);
        return $this->json($archive);
    }

    public function unarchive(Request $request, $taskId)
    {
        $user = $this->getUser($request);
        if (!$this->getKanbanModule()->isKanbanMemberByTaskId($user['id'], $taskId)) {
            throw new AccessDeniedException('Access Denied!');
        }
        $unarchive = $this->getKanbanTaskModule()->unarchive($taskId, $user['id']);
        return $this->json($unarchive);
    }

    public function archivedTasks(Request $request, $kanbanId)
    {
        $user = $this->getUser($request);
        $kanban = $this->getKanbanModule()->getByUuid($kanbanId, ['id']);
        if (!$kanban) {
            throw new ResourceNotFoundException('kanban.not_found');
        }
        $kanbanId = $kanban->id;
        if (!$this->getKanbanModule()->isAdmin($kanbanId, $user['id'])) {
            throw new AccessDeniedException();
        }
        $tasks = $this->getKanbanTaskModule()->getKanbanArchivedTasks($kanbanId, ['id', 'title']);
        return $this->json($tasks);
    }

    public function done(Request $request, $boardId, $id)
    {
        $user = $this->getUser($request);
        $done = $this->getKanbanTaskModule()->markDone($id, $user['id']);
        return $this->json($done);
    }

    public function undone(Request $request, $boardId, $id)
    {
        $user = $this->getUser($request);
        $undone = $this->getKanbanTaskModule()->markUndone($id, $user['id']);
        return $this->json($undone);
    }

    public function priority(Request $request, $boardId, $id)
    {
        $user = $this->getUser($request);
        $priority = $request->post('priority');
        $result = $this->getKanbanTaskModule()->setPriority($id, $priority, $user['id']);
        return $this->json($result);
    }

}
