<?php
namespace app\controller;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\common\toolkit\ArrayHelper;
use support\Request;
use app\module\Kanban as KanbanModule;
use support\Response;

class Kanban extends Base
{

    public function board(Request $request)
    {
        $user = $this->getUser($request);
        $keyword = $request->get('keyword', '');
        if ($this->getUserModule()->isSysAdmin($user['id'])) {
            $kanbans = $this->getKanbanModule()->getAllKanbans($keyword);
        } else {
            $kanbans = $this->getKanbanModule()->getUserKanbans($user['id'], $keyword);
        }
        $kanbanIds = $kanbans->pluck('id')->toArray();
        $kanbans = $this->getKanbanStatModule()->getKanbansProgress($kanbanIds);
        $currentUserRolesInKanbans = $this->getKanbanModule()->getUserInKanbansRole($user['id'], $kanbanIds);
        
        foreach ($kanbans as &$kanban) {
            $kanban->current_user_role = $currentUserRolesInKanbans[$kanban->id] ?? '';
        }
        
        return $this->json($kanbans);
    }

    protected function buildSearchTaskCond(Request $request, $kanbanId) : array
    {
        $cond = ['ids' => []];
        $query = $request->get();
        if (isset($query['labels'])) {
            if ($query['labels'] === 0 || $query['labels'] === "0") {// 搜索没有设置标签的卡片
                $cond['label_count'] = 0;
            } else {
                $labelIds = explode(',', $query['labels']);
                $labelIds = array_filter($labelIds,  function($v) {
                    return $v !== '' && $v !== null;
                });
                if ($labelIds) {
                    $taskIds = $this->getKanbanLabelModule()->getTaskIdsByKanbanIdAndLabelIds($kanbanId, $labelIds);
                    $taskIds = array_filter($taskIds,  function($v) {
                        return $v !== '' && $v !== null;
                    });
                    $cond['ids'] = array_merge($cond['ids'], $taskIds);
                }
            }
        }

        if (isset($query['prioritys'])) {
            $prioritys = explode(',', $query['prioritys']);
            $prioritys = array_filter($prioritys,  function($v) {
                return $v !== '' && $v !== null;
            });
            if ($prioritys) {
                $cond['prioritys'] = $prioritys;
            }
        }

        if (isset($query['userIds'])) {
            if ($query['userIds'] === "0" || $query['userIds'] === 0) { // 搜索没有指派成员的卡片
                $cond['member_count'] = 0;
            } else {
                $userIds = explode(',', $query['userIds']);
                $userIds = array_filter($userIds,  function($v) {
                    return $v !== '' && $v !== null;
                });
                if ($userIds) {
                    $taskIds = $this->getTaskMemberModule()->getUsersKanbanTaskIds($kanbanId, $userIds);
                    $taskIds = array_filter($taskIds,  function($v) {
                        return $v !== '' && $v !== null;
                    });
                    $cond['ids'] = array_merge($cond['ids'], $taskIds);
                }
            }
        }

        if (isset($query['keyword']) && strlen(trim($query['keyword'])) > 0) {
            $cond['keyword'] = trim($query['keyword']);
        }
        if (empty($cond['ids'])) {
            unset($cond['ids']);
        }
        if (isset($query['status'])) {
            if ($query['status'] !== '') {
                $cond['finished'] = $query['status'];
            }
        }
        $queryCond = ArrayHelper::parts($query, ['due']);
        return array_merge($cond, $queryCond);
    }

    public function detail(Request $request, $uuid) : Response
    {
        $kanban = $this->getKanbanModule()->getByUuid($uuid, ['id', 'uuid', 'name', 'desc', 'is_closed', 'user_id', 'created_time']);
        if (!$kanban) {
            throw new BusinessException('kanban.not_found');
        }
        if ($kanban->is_closed) {
            throw new BusinessException('kanban.closed');
        }
        $kanbanId = $kanban->id;
        $currentUser = $user = $this->getUser($request);
        if (!$this->getKanbanModule()->isMember($kanbanId, $user['id'])) {
            throw new AccessDeniedException();
        }
        
        $kanban['is_favorited'] = $this->getKanbanModule()->isFavorited($kanbanId, $user['id']);
        $kanban['user_role_admin'] = (bool) $this->getKanbanModule()->isAdmin($kanbanId, $user['id']);
        $kanbanList = $this->getKanbanModule()->getKanbanList($kanbanId);

        $kanbanIndexList = [];
        foreach ($kanbanList as $list) {
            $kanbanIndexList[$list->id] = $list;
        }
        $kanbanList = $kanbanIndexList;

        $taskSearchCond = $this->buildSearchTaskCond($request, $kanbanId);

        $sortField = $request->get('sort', 'default');
        $sortMethod = $request->get('sort_method', 'desc');

        $taskFields = [
            'id', 'title', 'priority', 
            'start_time', 'end_time', 
            'attachment_num', 'list_id', 
            'check_list_num', 'check_list_finished_num',
            'is_finished', 'create_user', 'created_time'
        ];

        $kanbanTasks = $this->getKanbanTaskModule()->searchKanbanTasks(
            $kanbanId, 
            $taskSearchCond,
            ['field' => $sortField, 'method' => $sortMethod],
            $taskFields
        );

        // 任务创建者
        $userIds = $kanbanTasks->pluck('create_user');
        $taskUsers = [];
        if ($userIds->isNotEmpty()) {
            $users = $this->getUserModule()->getByUserIds($userIds->toArray(), ['name']);
            foreach ($users as $user) {
                $taskUsers[$user['id']] = $user;
            }
        }
        // 任务成员
        $taskIds = $kanbanTasks->pluck('id');
        $taskMembers = [];
        if ($taskIds->toArray()) {
            $taskMembers = $this->getTaskMemberModule()->getTasksMembersInfo($taskIds->toArray());
        }

        // 自定义字段
        $customFields = $this->getCustomFieldModule()->getKanbanCustomFields($kanbanId);
        $customFieldsVal = $this->getTaskCustomeFieldModule()->getKanbanCustomFieldsVal($kanbanId);

        // 组装自定义字段数组，以自定义字段id为key，字段信息为值；如果是下拉框，以下拉框id为key，option为值；方便获取
        $indexCustomFields = [];
        foreach ($customFields as $customField) {
            if (!$customField->show_on_card_front) {
                continue;
            }
            if ($customField->type == \app\module\CustomField\CustomField::SUPPORT_TYPE_DROPDOWN) {
                $tmpOpts = [];
                foreach ($customField->options as $opt) {
                    $tmpOpts[$opt['id']] = $opt;
                }
                $customField->options = $tmpOpts;
            }
            $indexCustomFields[$customField->id] = $customField;
        }

        // 组装task_id-field_id为key，值为val的数组，方便前端获取
        $taskFieldVals = [];
        foreach ($customFieldsVal as $fieldVal) {
            if (!isset($indexCustomFields[$fieldVal->field_id])) {
                continue;
            }
            $field = $indexCustomFields[$fieldVal->field_id];
            if (!isset($taskFieldVals[$fieldVal->task_id])) {
                $taskFieldVals[$fieldVal->task_id] = [];
            }
            if ($field->type == \app\module\CustomField\CustomField::SUPPORT_TYPE_DROPDOWN) {
                if (isset($field->options[$fieldVal->val])) {
                    $taskFieldVals[$fieldVal->task_id][$fieldVal->field_id] = $field->options[$fieldVal->val]['val'];
                }
            } else {
                $taskFieldVals[$fieldVal->task_id][$fieldVal->field_id] = $fieldVal->val;
            }
        }

        $tasksLabels = $this->getKanbanLabelModule()->getTasksLabelsGroupByTaskId($taskIds->toArray());

        $taskGroupByList = [];
        foreach ($kanbanTasks as &$t) {
            $t->members = $taskMembers[$t->id] ?? [];
            $t->creator = $taskUsers[$t->user_id] ?? [];
            $t->labels = $tasksLabels[$t->id] ?? [];
            $t->done = $t->is_finished ? true : false;
            $t->end_date = $t->end_time ? $t->end_date = date('Y-m-d H:i', $t->end_time) : '';
            $t->is_due_soon = $t->end_time - time() < 86400 && time() < $t->end_time; // 是否即将过期
            $t->overfall = $t->end_time > 0 && time() > $t->end_time; // 已过期
            $t->custom_fields = $indexCustomFields;
            $t->task_custom_field_vals = $taskFieldVals[$t->id] ?? new \stdClass();

            if (!isset($taskGroupByList[$t->list_id])) {
                $taskGroupByList[$t->list_id] = [];
            }
            $taskGroupByList[$t->list_id][] = $t;
        }

        foreach ($kanbanList as &$l) {
            $listTasks = $taskGroupByList[$l->id] ?? [];
            $l->tasks = $listTasks;
        }

        $labels = $this->getKanbanLabelModule()->getKanbanLabels($kanbanId, ['id', 'name', 'color']);
        $kanban->project = $this->getProjectModule()->getProjectByKanbanId($kanbanId, ['uuid', 'name']);

        $data = [
            'kanban' => $kanban,
            'kanban_labels' => $labels,
            'label_colors' => $this->getKanbanLabelModule()->getDefaultColors(),
            'list' => array_values($kanbanList), // 返回数组
            'tasks' => $kanbanTasks,
            'list_tasks' => $taskGroupByList,
            'custom_fields' => $indexCustomFields
        ];

        try {
            $this->getKanbanModule()->viewKanban($kanbanId, $currentUser['id']);
        } catch (\Exception $e) {
            $this->getLogger()->error(sprintf('view kanban error %s', $e->getMessage()), $e->getTrace());
            throw $e;
        }

        return $this->json($data);
    }

    public function simple(Request $request, $id)
    {
        $user = $this->getUser($request);
        if (!$this->getKanbanModule()->isMember($id, $user['id'])) {
            throw new AccessDeniedException('Access Denied!');
        }
        $kanban = $this->getKanbanModule()->get($id, ['id', 'name', 'is_closed', 'user_id', 'created_time']);

        return $this->json($kanban);
    }

    public function recentlyView(Request $request)
    {
        $user = $this->getUser($request);

        $kanbans = $this->getKanbanModule()->getRecentlyViewedKanbans($user['id'], 6);
        $projects = $this->getProjectModule()->getRecentlyViewedProjects($user['id'], 6);

        return $this->json(['kanbans' => $kanbans, 'projects' => $projects]);
    }

    public function search(Request $request)
    {
        $keyword = $request->get('keyword', '');
        $page = (int) $request->get('paget', 1);
        $pageSize = $request->get('page_size', 10);
        $user = $this->getUser($request);
        $start = ($page - 1) * $pageSize;

        $kanbans = $this->getKanbanModule()->searchUserKanbans($user['id'], $keyword, ['*'], $start, $pageSize);
        return $this->json($kanbans);
    }

    public function my(Request $request)
    {
        $user = $this->getUser($request);
        $keyword = $request->get('keyword', '');
        $kanbans = $this->getKanbanModule()->getUserKanbans($user['id'], $keyword, ['id', 'uuid', 'name']);
        $kanbanIds = [];
        foreach ($kanbans as $kanban) {
            $kanbanIds[] = $kanban['id'];
        }
        $lists = $this->getKanbanModule()->getKanbansList($kanbanIds, ['id', 'kanban_id', 'name', 'wip', 'task_count']);
        $kanbanLists = [];
        foreach ($lists as $list) {
            if (!isset($kanbanLists[$list->kanban_id])) {
                $kanbanLists[$list->kanban_id] = [];
            }
            $kanbanLists[$list->kanban_id][] = $list;
        }
        foreach ($kanbans as &$kanban) {
            $kanban['list'] = $kanbanLists[$kanban->id] ?? [];
            unset($kanban->id);
        }
        return $this->json($kanbans);
    }

    public function closed(Request $request)
    {
        $user = $this->getUser($request);

        $kanbans = $this->getKanbanModule()->getUserClosedKanbans($user['id'], ['*']);
        return $this->json($kanbans);
    }

    public function add(Request $request)
    {
        $name = $request->post('name', '');
        $desc = $request->post('desc', '');
        $fromUUid = $request->post('from_uuid', '');
        $projectUUid = $request->post('project_uuid');
        $user = $this->getUser($request);
        if ($projectUUid && !$this->getProjectModule()->isMember($projectUUid, $user['id'])) {
            throw new AccessDeniedException();
        }

        if ($fromUUid) {
            $newKanban = $this->getKanbanModule()->createFrom($fromUUid, $name, $desc, $user['id']);
        } else {
            $newKanban = $this->getKanbanModule()->create($name, $desc, $user['id']);
        }
        if ($projectUUid) {
            $this->getProjectModule()->addKanbanToProject($newKanban->id, $projectUUid, $user['id']);
            $this->getProjectModule()->copyProjectMemberToKanban($projectUUid, $newKanban->id, $user['id']);
        }
        return $this->json(['kanban_uuid' => $newKanban->uuid]);
    }

    public function join(Request $request)
    {
        $currentUser = $this->getUser($request);
        $joinUserId = $request->post('user_id', 0);
        $uuid = $request->post('kanban_id', 0);
        $role = $request->post('role', KanbanModule::MEMBER_ROLE_USER);

        $kanban = $this->getKanbanModule()->getByUuid($uuid, ['id', 'uuid']);
        if (!$kanban) {
            throw new BusinessException('kanban.not_found');
        }
        $kanbanId = $kanban->uuid;

        if (!$this->getKanbanModule()->isAdmin($kanbanId, $currentUser['id'])) {
            throw new AccessDeniedException();
        }
        $joined = $this->getKanbanModule()->joinKanban($kanbanId, $joinUserId, $role);

        return $this->json(['joined' => $joined]);
    }

    public function update(Request $request, $id)
    {
        $name = $request->post('name', '');
        $desc = $request->post('desc', '');
        $user = $this->getUser($request);

        $changed = $this->getKanbanModule()->updateKanbanNameAndDesc($id, $name, $desc, $user['id']);

        return $this->json($changed);
    }

    public function setWip(Request $request, $id)
    {
        $wips = $request->post();
        $user = $this->getUser($request);
        foreach ($wips as $listId => $wip) {
            $this->getKanbanModule()->setWip($listId, $wip, $user['id']);
        }
        return $this->json(true);
    }

    public function close(Request $request, $id)
    {
        $user = $this->getUser($request);
        
        if (!$this->getKanbanModule()->isAdmin($id, $user['id'])) {
            throw new AccessDeniedException();
        }
        $closed = $this->getKanbanModule()->close($id);

        return $this->json(['closed' => $closed]);
    }

    public function cancelClose(Request $request, $id)
    {
        $user = $this->getUser($request);
        
        if (!$this->getKanbanModule()->isAdmin($id, $user['id'])) {
            throw new AccessDeniedException();
        }
        $unclose = $this->getKanbanModule()->unclose($id);

        return $this->json(['unclose' => $unclose]);
    }

    public function favorite(Request $request, $id)
    {
        $user = $this->getUser($request);

        $kanban = $this->getKanbanModule()->getByUuid($id, ['id']);
        if (!$kanban) {
            throw new BusinessException('kanban.not_found');
        }

        $favorite = $this->getKanbanModule()->favorite($kanban->id, $user['id']);
        return $this->json($favorite);
    }

    public function unfavorite(Request $request, $id)
    {
        $user = $this->getUser($request);

        $kanban = $this->getKanbanModule()->getByUuid($id, ['id']);
        if (!$kanban) {
            throw new BusinessException('kanban.not_found');
        }

        $unfivorate = $this->getKanbanModule()->unfavorite($kanban->id, $user['id']);
        return $this->json($unfivorate);
    }

    public function dashborad(Request $request, $id) : Response
    {
        $user = $this->getUser($request);
        $kanban = $this->getKanbanModule()->getByUuid($id, ['id']);
        if (!$kanban) {
            throw new BusinessException('kanban.not_found');
        }
        $id = $kanban->id;
        if (!$this->getKanbanModule()->isMember($id, $user['id'])) {
            throw new AccessDeniedException();
        }

        $data = $this->getKanbanStatModule()->dashboard($id);
        $kanban = $this->getKanbanModule()->get($id, ['id', 'uuid', 'name', 'desc', 'is_closed', 'user_id', 'created_time']);
        $kanban->project = $this->getProjectModule()->getProjectByKanbanId($id, ['uuid', 'name']);
        if ($kanban->is_closed) {
            throw new BusinessException('Kanban Closed');
        }
        $kanban['is_favorited'] = $this->getKanbanModule()->isFavorited($id, $user['id']);
        $data['kanban'] = $kanban;
        return $this->json($data);
    }

}
