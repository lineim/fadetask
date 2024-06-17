<?php
/**
 * This file is part of fade task project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\module;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\common\exception\InvalidParamsException;
use app\common\exception\ResourceNotFoundException;
use app\model\KanbanList as KanbanListModel;
use \app\model\KanbanTask as KanbanTaskModel;
use app\model\KanbanTaskLabel;
use app\model\KanbanTaskLabel as KanbanTaskLabelModel;
use \app\model\KanbanTaskLog as KanbanTaskLogModel;
use \app\model\KanbanTaskMember as KanbanTaskMemberModel;
use app\module\Kanban as KanbanModule;
use Ramsey\Uuid\Uuid;
use support\Db;
use Illuminate\Database\Eloquent\Collection;
use app\module\Notification\Factory;
use app\model\TaskCheckList as KanbanTaskCheckListModel;
use app\model\KanbanTaskAttachment as KanbanTaskAttachmentModel;
use app\model\Kanban as KanbanModel;
use app\model\KanbanLabel as KanbanLabelModel;
use app\model\CheckListMember as CheckListMemberModel;
use app\common\toolkit\DateTime as DateTimeHelper;

class KanbanTask extends BaseModule
{
    const TASK_DELTED = 1;
    const TASK_NOT_DELETED = 0;

    const TASK_ARCHIVED = 1;
    const TASK_NOT_ARCHIVED = 0;

    const TASK_DONE = 1;
    const TASK_UNDONE = 0;

    const PRIORITY_EMERGENCTY = 0;
    const PRIORITY_HIGH = 1;
    const PRIORITY_NORMAL = 2;
    const PRIORITY_LOW = 3;

    const WIP_NO_LIMIT = 0;

    const PRIORITY_EMERGENCTY_TXT = '紧急';
    const PRIORITY_HIGH_TXT = '高';
    const PRIORITY_NORMAL_TXT = '普通';
    const PRIORITY_LOW_TXT = '低';

    const TASK_LOG_ACTION_CREATE = 'create';
    const TASK_LOG_ACTION_CHANGE = 'change';
    const TASK_LOG_ACTION_CHANGE_LIST = 'change_list';
    const TASK_LOG_ACTION_ADD_MEMBER = 'add_member';
    const TASK_LOG_ACTION_REMOVE_MEMBER = 'remove_member';
    const TASK_LOG_ACTION_CHANGE_TITLE = 'change_title';
    const TASK_LOG_ACTION_CHANGE_DESC = 'change_desc';
    const TASK_LOG_ACTION_DONE   = 'done';
    const TASK_LOG_ACTION_DONE_AUTO = 'done_auto';
    const TASK_LOG_ACTION_UNDONE   = 'undone';
    const TASK_LOG_ACTION_ARCHIVED = 'archive';
    const TASK_LOG_ACTION_UNARCHIVED = 'unarchive';
    const TASK_LOG_ACTION_SET_DUEDATE = 'set_duedate';
    const TASK_LOG_ACTION_MOVE_TO_OTHER = 'move_to_other'; // 移动到其他看板
    const TASK_LOG_ACTION_ADD_CHECKLIST = 'add_check_list';
    const TASK_LOG_ACTION_DONE_CHECKLIST = 'done_check_list';
    const TASK_LOG_ACTION_UNDONE_CHECKLIST = 'undone_check_list';
    const TASK_LOG_ACTION_ADD_ATTACHMENT = 'add_attachment';
    const TASK_LOG_ACTION_DEL_ATTACHMENT = 'del_attachment';
    const TASK_LOG_ACTION_SET_PRIORITY = 'set_priority';
    const TASK_LOG_ACTION_ADD_COMMENT = 'add_comment';
    const TASK_LOG_ACTION_EDIT_COMMENT = 'edit_comment';
    const TASK_LOG_ACTION_DEL_COMMENT = 'del_comment';

    const TASK_DUE_NOTIFY_TIME_ON_TIME = -1; // 到时间就通知
    const TASK_DUE_NOTIFY_TIME_NULL = 0; // 不通知
    const TASK_DUE_NOTIFY_TIME_ONE_MIN = 60; // 一分钟
    const TASK_DUE_NOTIFY_TIME_FIVE_MIN = 300; 
    const TASK_DUE_NOTIFY_TIME_TEN_MIN = 600; 
    const TASK_DUE_NOTIFY_TIME_FIFTEEN_MIN = 900; 
    const TASK_DUE_NOTIFY_TIME_ONE_HOUR = 3600; 
    const TASK_DUE_NOTIFY_TIME_TWO_HOUR = 7200; 
    const TASK_DUE_NOTIFY_TIME_ONE_DAY = 86400; 
    const TASK_DUE_NOTIFY_TIME_TWO_DAY = 1728600; 

    const TASK_DUE_NOTIFY_TIME_ON_TIME_TXT = "到期通知"; // 到时间就通知
    const TASK_DUE_NOTIFY_TIME_NULL_TXT = "不通知"; // 不通知
    const TASK_DUE_NOTIFY_TIME_ONE_MIN_TXT = "1分钟前"; // 一分钟
    const TASK_DUE_NOTIFY_TIME_FIVE_MIN_TXT = "5分钟前"; 
    const TASK_DUE_NOTIFY_TIME_TEN_MIN_TXT = "10分钟前"; 
    const TASK_DUE_NOTIFY_TIME_FIFTEEN_MIN_TXT = "15分钟前"; 
    const TASK_DUE_NOTIFY_TIME_ONE_HOUR_TXT = "1小时前"; 
    const TASK_DUE_NOTIFY_TIME_TWO_HOUR_TXT = "2小时前"; 
    const TASK_DUE_NOTIFY_TIME_ONE_DAY_TXT = "1天前"; 
    const TASK_DUE_NOTIFY_TIME_TWO_DAY_TXT = "2天前"; 

    const TASK_DUE_NOTIFY_SENDED = 1; // 到期通知已发送
    const TASK_DUE_NOTIFY_UNSEND = 0; // 到期通知未发送

    public function getTaskLogActions()
    {
        return [
            self::TASK_LOG_ACTION_CREATE,
            self::TASK_LOG_ACTION_CHANGE,
            self::TASK_LOG_ACTION_CHANGE_LIST,
            self::TASK_LOG_ACTION_ADD_MEMBER,
            self::TASK_LOG_ACTION_REMOVE_MEMBER,
            self::TASK_LOG_ACTION_CHANGE_TITLE,
            self::TASK_LOG_ACTION_CHANGE_DESC,
            self::TASK_LOG_ACTION_DONE,
            self::TASK_LOG_ACTION_DONE_AUTO,
            self::TASK_LOG_ACTION_UNDONE,
            self::TASK_LOG_ACTION_ARCHIVED,
            self::TASK_LOG_ACTION_UNARCHIVED,
            self::TASK_LOG_ACTION_SET_DUEDATE,
            self::TASK_LOG_ACTION_MOVE_TO_OTHER,
            self::TASK_LOG_ACTION_ADD_CHECKLIST,
            self::TASK_LOG_ACTION_DONE_CHECKLIST,
            self::TASK_LOG_ACTION_UNDONE_CHECKLIST,
            self::TASK_LOG_ACTION_ADD_ATTACHMENT,
            self::TASK_LOG_ACTION_DEL_ATTACHMENT,
            self::TASK_LOG_ACTION_SET_PRIORITY,
            self::TASK_LOG_ACTION_ADD_COMMENT,
            self::TASK_LOG_ACTION_EDIT_COMMENT,
            self::TASK_LOG_ACTION_DEL_COMMENT,
        ];
    }

    public function getDueNotifyTimes()
    {
        return [
            self::TASK_DUE_NOTIFY_TIME_ON_TIME,
            self::TASK_DUE_NOTIFY_TIME_NULL,
            self::TASK_DUE_NOTIFY_TIME_ONE_MIN,
            self::TASK_DUE_NOTIFY_TIME_FIVE_MIN,
            self::TASK_DUE_NOTIFY_TIME_TEN_MIN,
            self::TASK_DUE_NOTIFY_TIME_FIFTEEN_MIN,
            self::TASK_DUE_NOTIFY_TIME_ONE_HOUR,
            self::TASK_DUE_NOTIFY_TIME_TWO_HOUR,
            self::TASK_DUE_NOTIFY_TIME_ONE_DAY,
            self::TASK_DUE_NOTIFY_TIME_TWO_DAY,
        ];
    }


    public function getDueNotifyTimeTxt()
    {
        return [
            self::TASK_DUE_NOTIFY_TIME_ON_TIME => self::TASK_DUE_NOTIFY_TIME_ON_TIME_TXT,
            self::TASK_DUE_NOTIFY_TIME_NULL => self::TASK_DUE_NOTIFY_TIME_NULL_TXT,
            self::TASK_DUE_NOTIFY_TIME_ONE_MIN => self::TASK_DUE_NOTIFY_TIME_ONE_MIN_TXT,
            self::TASK_DUE_NOTIFY_TIME_FIVE_MIN => self::TASK_DUE_NOTIFY_TIME_FIVE_MIN_TXT,
            self::TASK_DUE_NOTIFY_TIME_TEN_MIN => self::TASK_DUE_NOTIFY_TIME_TEN_MIN_TXT,
            self::TASK_DUE_NOTIFY_TIME_FIFTEEN_MIN => self::TASK_DUE_NOTIFY_TIME_FIFTEEN_MIN_TXT,
            self::TASK_DUE_NOTIFY_TIME_ONE_HOUR => self::TASK_DUE_NOTIFY_TIME_ONE_HOUR_TXT,
            self::TASK_DUE_NOTIFY_TIME_TWO_HOUR => self::TASK_DUE_NOTIFY_TIME_TWO_HOUR_TXT,
            self::TASK_DUE_NOTIFY_TIME_ONE_DAY => self::TASK_DUE_NOTIFY_TIME_ONE_DAY_TXT,
            self::TASK_DUE_NOTIFY_TIME_TWO_DAY => self::TASK_DUE_NOTIFY_TIME_TWO_DAY_TXT,
        ];
    }

    public function getTask($id, array $fields = ['*'])
    {
        if (!in_array('*', $fields)) {
            $fields[] = 'end_time';
            $fields[] = 'is_finished';
        }
        $task = KanbanTaskModel::where('id', $id)->first(array_unique($fields));
        if (!$task) {
            return $task;
        }
        $task->end_date = $task->end_time ? date('Y-m-d H:i:s', $task->end_time) : '';
        $task->done = (bool)$task->is_finished;
        $task->is_due_soon = $task->end_time - time() < 86400 && time() < $task->end_time; // 是否即将过期
        $task->overfall = $task->end_time > 0 && time() > $task->end_time; // 已过期

        return $task;
    }

    public function getKanbanTasks($kanbanId, $fields = ['*'], $withNotEnable = false)
    {
        $model = KanbanTaskModel::where('kanban_id', $kanbanId);

        if (!$withNotEnable) {
            $model = $model->where('is_delete', self::TASK_NOT_DELETED)
            ->where('archived', self::TASK_NOT_ARCHIVED);
        }
        return $model->orderBy('list_sort', 'ASC')->get($fields);
    }

    public function getKanbansTasks(array $kanbanIds, $fields = ['*'], $withNotEnable = false)
    {
        $model = KanbanTaskModel::whereIn('kanban_id', $kanbanIds);

        if (!$withNotEnable) {
            $model = $model->where('is_delete', self::TASK_NOT_DELETED)
            ->where('archived', self::TASK_NOT_ARCHIVED);
        }
        return $model->orderBy('list_sort', 'ASC')->get($fields);
    }

    public function getSubtasks($parentId, $userId, $fields = ['*'])
    {
        if (!$this->isKanbanMemberByTaskId($userId, $parentId)) {
            throw new AccessDeniedException();
        }
        
        return KanbanTaskModel::where('parent_id', $parentId)
            ->where('is_delete', self::TASK_NOT_DELETED)
            ->where('archived', self::TASK_NOT_ARCHIVED)
            ->orderBy('id', 'ASC')
            ->get($fields);
    }

    public function getSubtasksCount($parentId)
    {
        return KanbanTaskModel::where('parent_id', $parentId)
            ->where('is_delete', self::TASK_NOT_DELETED)
            ->where('archived', self::TASK_NOT_ARCHIVED)
            ->count();
    }

    public function searchKanbanTasks($kanbanId, array $cond, $sort = [], $fields = '*')
    {
        $taskTable = (new KanbanTaskModel())->getTable();
        $listTable = (new KanbanListModel())->getTable();
        $model = KanbanTaskModel::where($taskTable.'.kanban_id', $kanbanId)
            ->join($listTable, $taskTable.'.list_id', '=', $listTable.'.id')
            ->where($taskTable.'.is_delete', self::TASK_NOT_DELETED)
            ->where($taskTable.'.archived', self::TASK_NOT_ARCHIVED)
            ->where($listTable.'.archived', 0);
        
        if (isset($cond['ids'])) {
            $model = $model->whereIn($taskTable.'.id', $cond['ids']);
        }

        if (isset($cond['keyword']) && strlen($cond['keyword']) > 0) {
            $model = $model->where($taskTable.'.title', 'like', '%' . $cond['keyword'] . '%');
        }

        if (isset($cond['finished'])) {
            $isFinished = $cond['finished'] ? 1 : 0;
            $model = $model->where($taskTable.'.is_finished', $isFinished);
        }

        if (isset($cond['member_count'])) {
            $model = $model->where($taskTable.'.member_count', (int) $cond['member_count']);
        }

        if (isset($cond['label_count'])) {
            $model = $model->where($taskTable.'.label_count', (int) $cond['label_count']);
        }

        if (isset($cond['prioritys'])) {
            $model = $model->whereIn($taskTable.'.priority', $cond['prioritys']);
        }

        if (isset($cond['over_due'])) {
            $model = $model->where($taskTable.'.end_time', '<', time());
        }
        if (isset($cond['parent_id'])) {
            $model = $model->where($taskTable.'.parent_id', $cond['parent_id']);
        }

        if (isset($cond['due'])) {
            $due = $cond['due'];
            if ($due == 'over_due') {
                $model = $model->where($taskTable.'.end_time', '<', time())->where($taskTable.'.end_time', '>', 0);
            }
            if ($due == 'today_due') {
                $starttime = strtotime(date('Y-m-d 00:00:00', time()));
                $endtime = strtotime(date('Y-m-d 23:59:59', time()));
                $model = $model->whereBetween($taskTable.'.end_time', [$starttime, $endtime]);
            }
            if ($due == 'this_week_due') {
                $starttime = strtotime(DateTimeHelper::firstDayThisWeek() . ' 00:00:00');
                $endtime = strtotime(DateTimeHelper::lastDayThisWeek() . ' 23:59:59');
                $model = $model->whereBetween($taskTable.'.end_time', [$starttime, $endtime]);
            }
            if ($due == 'next_week_due') {
                $starttime = strtotime(DateTimeHelper::firstDayNextWeek() . ' 00:00:00');
                $endtime = strtotime(DateTimeHelper::lastDayNextWeek() . ' 23:59:59');
                $model = $model->whereBetween($taskTable.'.end_time', [$starttime, $endtime]);
            }
            if ($due == 'no_due') {
                $model = $model->where($taskTable.'.end_time', 0);
            }
        }

        $sort = $this->buildTaskSearchSort($sort);

        $tasks = $model->orderBy($taskTable .'.'.$sort['field'], $sort['method'])
            ->select($taskTable.".*")
            ->get($fields);
        return $tasks;
    }

    protected function buildTaskSearchSort(array $sort)
    {
        $sortMethod = isset($sort['method']) && in_array($sort['method'], ['desc', 'asc']) ? $sort['method'] : 'desc';
        $sortField = 'list_sort';
        if (!isset($sort['field'])) {
            return ['field' => $sortField, 'method' => $sortMethod];
        }
        
        switch ($sort['field']) {
            case 'name':
                $sortField = 'title';
                break;
            case 'priority':
                $sortField = 'priority';
                // 优先级数字越低，优先级越高，所以需要调换
                $sortMethod = $sortMethod == 'desc' ? 'asc' : 'desc'; 
                break;
            // 创建时间
            case 'time':
                $sortField = 'id';
                break;
            case 'due_date':
                $sortField = 'end_time';
                break;
            default:
                $sortField = 'list_sort';
                $sortMethod = 'asc';
            
        }
        return ['field' => $sortField, 'method' => $sortMethod];
    }

    public function getTasksByKanbanIds(array $kanbanIds, $fields = ['*'])
    {
        return KanbanTaskModel::whereIn('kanban_id', $kanbanIds)
            ->where('is_delete', self::TASK_NOT_DELETED)
            ->get($fields);
    }

    public function createTask(array $task, $userId, $listId = 0)
    {
        $kanbanId = $task['kanbanId'] ?? 0;
        if (!$this->getKanbanModule()->isMember($kanbanId, $userId)) {
            throw new AccessDeniedException();
        }
        $kanban = $this->getKanbanModule()->get($kanbanId, ['id']);
        if (!$kanban) {
            throw new ResourceNotFoundException("Kanban not found");
        }
        $parentId = 0;
        if ($task['parentId']) {
            $parentTask = $this->getTask($task['parentId'], ['kanban_id', 'parent_id', 'subtask_count']);
            if (!$parentTask) {
                throw new ResourceNotFoundException('task.parent_not_found');
            }
            if ($parentTask->kanban_id != $kanbanId) {
                throw new BusinessException('Parent task not belong the same kanban!');
            }
            if ($parentTask['parent_id'] > 0) { // 只支持两级子任务
                $parentId = $parentTask['parent_id'];
                $parentTask = $this->getTask($parentId, ['kanban_id', 'parent_id', 'subtask_count']);
            } else {
                $parentId = $task['parentId'];
            }
        }

        $title = $task['title'] ?? '';
        if (!$title) {
            throw new InvalidParamsException("Invalid Task Title");
        }
        if (!(int) $userId) {
            throw new InvalidParamsException("Invalid User Id");
        }
        if (!$listId) {
            $listId = $this->getKanbanModule()->getKanbanFirstList($kanbanId);
        } else {
            if (!$this->getKanbanModule()->isListBelongKanban($listId, $kanbanId)) {
                throw new InvalidParamsException("Invalid List");
            }
        }

        $list = $this->getKanbanModule()->getList($listId, ['task_count', 'wip']);
        if ($list->wip && $list->task_count >= $list->wip) {
            throw new BusinessException('task.create.wip_limited_msg');
        }

        $newTask = new KanbanTaskModel();
        $newTask->parent_id = $parentId;
        $newTask->title = $title;
        $newTask->uuid = Uuid::uuid4()->toString();
        $newTask->desc  = $task['desc'] ?? '';
        $newTask->start_time = (int) ($task['start_time'] ?? 0);
        $newTask->end_time = (int) ($task['end_time'] ?? 0);
        $newTask->kanban_id = $kanbanId;
        $newTask->create_user = $userId;
        $newTask->list_id = $listId;
        $newTask->created_time = time();
        $newTask->save();

        if ($parentId) {
            KanbanTaskModel::where('id', $parentId)->update(['subtask_count' => $parentTask->subtask_count += 1]);
        }

        // Todo: +1 时加锁
        $list = KanbanListModel::where('id', $listId)->first();
        $list->task_count = $list->task_count + 1;
        $list->save();

        $log = new KanbanTaskLogModel();
        $log->kanban_id = $kanbanId;
        $log->task_id = $newTask->id;
        $log->user_id = $userId;
        $log->action = self::TASK_LOG_ACTION_CREATE;
        $log->created_time = time();
        $log->save();

        return $newTask->id;
    }

    public function waveTaskCommentNum($taskId, $num)
    {
        if ($num > 0) {
            return KanbanTaskModel::where('id', $taskId)->increment('comment_num', $num);
        }
        return KanbanTaskModel::where('id', $taskId)->decrement('comment_num', abs($num));
    }

    protected function updateTask($id, array $fields)
    {
        return KanbanTaskModel::where('id', $id)->update($fields);
    }

    /**
     * 设置任务成员, 支持幂等.
     */
    public function setMembers($id, array $userIds, $operatorId)
    {
        if (!$userIds) {
            throw new InvalidParamsException('Member Id Required!');
        }
        $task = $this->getTask($id, ['id', 'list_id', 'kanban_id', 'title']);
        if (!$task) {
            throw new ResourceNotFoundException('task.not_found');
        }
        if (!$this->getKanbanModule()->isMember($task->kanban_id, $operatorId)) {
            throw new AccessDeniedException();
        }
        $kanban = $this->getKanbanModule()->get($task->kanban_id, ['uuid']);
        if (!$kanban) {
            throw new ResourceNotFoundException('kanban.not_found');
        }
        $task->kanban_uuid = $kanban->uuid;
        $members = KanbanTaskMemberModel::where('task_id', $id)
            ->whereIn('member_id', $userIds)
            ->get(['member_id']);
        $existMemberIds = [];
        foreach ($members as $m) {
            $existMemberIds[] = $m->member_id;
        }
        $addUserIds = array_diff($userIds, $existMemberIds);
        if (!$addUserIds) {
            return true;
        }

        $addUserNum = $this->getUserModule()->getCountByUserIds($addUserIds);
        if ($addUserNum != count($addUserIds)) {
            throw new BusinessException("Some user not exist!");
        }

        $newMembers = [];
        $time = time();
        $addUserIds = array_unique($addUserIds);
        $needNotificationUserIds = [];
        foreach ($addUserIds as $uid) {
            $newMembers[] = [
                'task_id' => $id,
                'member_id' => $uid,
                'created_time' => $time,
            ];
            if ($uid != $operatorId) {
                $needNotificationUserIds[] = $uid;
            }
        }

        $log = new KanbanTaskLogModel();
        $log->kanban_id = $task->kanban_id;
        $log->task_id = $task->id;
        $log->user_id = $operatorId;
        $log->action = self::TASK_LOG_ACTION_ADD_MEMBER;
        $log->change = json_encode(['new_member_ids' => array_values($addUserIds)]);
        $log->created_time = time();
        $log->save();

        $added = KanbanTaskMemberModel::insert($newMembers);
        if ($added) {
            $this->waveTaskMemberCount($id, count($newMembers));
            $operator = $this->getUserModule()->getByUserId($operatorId, ['name']);
            $this->sendAddToCardNotification($needNotificationUserIds, $task, $operator);
        }
        return $added;
    }

    protected function sendAddToCardNotification(array $receiverIds, $task, $operator) : void
    {
        if (empty($receiverIds)) {
            return;
        }
        // 站内信
        $params = ['task' => $task, 'operator' => $operator];
        $this->getNotificationModule()->batchNew($receiverIds, Notification::TPL_JOIN_TASK, $params);
        // 邮件
        $users = $this->getUserModule()->getByUserIds($receiverIds, ['email', 'verified']);
        $receiverEmails = [];
        foreach ($users as $user) {
            if ($user->verified) {
                $receiverEmails[] = $user->email;
            }
        }
        if (empty($receiverEmails)) {
            return;
        }
        
        try {
            $channel = Factory::getChannel('mail', 'queue', 'join_task_notify');
            $channel->sendBatchMsgV2($receiverEmails, $params);
        } catch (\Exception $e) {
            $this->getLogger()->error(sprintf('send add to card notify failed: %s, trace: %s', $e->getMessage(), $e->getTraceAsString()));
        }
    }

    public function removeMembers($id, array $userIds, $operator)
    {
        if (!$userIds) {
            throw new InvalidParamsException('Member Id Required!');
        }
        $task = $this->getTask($id, ['id', 'list_id', 'kanban_id']);
        if (!$task) {
            throw new BusinessException('Task Not Found!');
        }
        if (!$this->getKanbanModule()->isMember($task->kanban_id, $operator)) {
            throw new AccessDeniedException('Access Denied!');
        }
        $members = KanbanTaskMemberModel::where('task_id', $id)
            ->whereIn('member_id', $userIds)
            ->get(['id', 'member_id']);
        $removeMemberIds = [];
        $removeIds = [];
        foreach ($members as $m) {
            $removeMemberIds[] = $m->member_id;
            $removeIds[] = $m->id;
        }
        if (!$removeMemberIds) {
            return true;
        }
        $removed = KanbanTaskMemberModel::whereIn('id', $removeIds)->delete();

        $log = new KanbanTaskLogModel();
        $log->kanban_id = $task->kanban_id;
        $log->task_id = $task->id;
        $log->user_id = $operator;
        $log->action = self::TASK_LOG_ACTION_REMOVE_MEMBER;
        $log->change = json_encode(['remove_member_ids' => array_values($removeMemberIds)]);
        $log->created_time = time();
        $log->save();

        if ($removed) {
            $this->waveTaskMemberCount($id, 0 - count($removeIds));
        }

        return false !== $removed;
    }

    public function getMebmerByTaskIdAndMemberId($taskId, $memberId, array $fields = ['*'])
    {
        return KanbanTaskMemberModel::where('task_id', $taskId)->where('member_id', $memberId)->first($fields);
    }

    public function rmMemberFromTasks($memberId, array $taskIds)
    {
        return KanbanTaskMemberModel::where('member_id', $memberId)->whereIn('task_id', $taskIds)->delete();
    }

    /**
     * 修改 task 所在的列, 支持幂等.
     */
    public function changeList($id, $listId, $operator)
    {
        $task = $this->getTask($id, ['id', 'list_id', 'kanban_id']);
        if (!$task) {
            throw new BusinessException('Task Not Found!');
        }
        if ($task->list_id == $listId) {
            return true;
        }

        $kanbanList = KanbanListModel::where('id', $listId)
            ->where('kanban_id', $task->kanban_id)
            ->first(['kanban_id', 'task_count', 'wip', 'completed']);
        if (!$kanbanList) {
            throw new BusinessException('kanban.list.not_found');
        }

        if ($kanbanList->wip > 0 && $kanbanList->task_count >= $kanbanList->wip) {
            throw new BusinessException("task.wip.limited");
        }

        $update = ['list_id' => $listId, 'updated_time' => time()];

        $updated = $this->updateTask($id, $update);

        if (!$updated) {
            return false;
        }

        // 新的列，task count + 1
        KanbanListModel::where('id', $listId)
            ->where('kanban_id', $task->kanban_id)
            ->increment('task_count');
        
        // 老的列, task count - 1
        KanbanListModel::where('id', $task->list_id)
            ->where('kanban_id', $task->kanban_id)
            ->decrement('task_count');

        if ($kanbanList->completed) {
            $this->markDone($id, $operator, true);
        }
        

        $log = new KanbanTaskLogModel();
        $log->kanban_id = $task->kanban_id;
        $log->task_id = $task->id;
        $log->user_id = $operator;
        $log->action = self::TASK_LOG_ACTION_CHANGE_LIST;
        $log->change = json_encode(['from' => $task->list_id, 'to' => $listId]);
        $log->created_time = time();
        $log->save();

        return false !== $updated;
    }

    public function archive($id, $operator)
    {
        $task = $this->getTask($id, ['id', 'kanban_id', 'list_id', 'archived']);
        if ($task->archived) {
            return true;
        }
        $archived = $this->updateTask($id, ['archived' => self::TASK_ARCHIVED]);
        KanbanListModel::where('id', $task->list_id)->decrement('task_count');

        $log = new KanbanTaskLogModel();
        $log->kanban_id = $task->kanban_id;
        $log->task_id = $task->id;
        $log->user_id = $operator;
        $log->action = self::TASK_LOG_ACTION_ARCHIVED;
        $log->change = '';
        $log->created_time = time();
        $log->save();

        return false !== $archived;
    }

    public function unarchive($id, $operator)
    {
        $task = $this->getTask($id, ['id', 'kanban_id', 'list_id', 'archived']);
        if (!$task->archived) {
            return true;
        }

        $list = $this->getKanbanModule()->getList($task->list_id);
        if (!$list) {
            throw new BusinessException('task.unarchive.error.list_not_found');
        }

        if ($list->wip > 0 && $list->task_count >= $list->wip) {
            throw new BusinessException('task.unarchive.error.wip_limit');
        }

        $unarchived = $this->updateTask($id, ['archived' => self::TASK_NOT_ARCHIVED]);
        KanbanListModel::where('id', $task->list_id)->increment('task_count');

        $log = new KanbanTaskLogModel();
        $log->kanban_id = $task->kanban_id;
        $log->task_id = $task->id;
        $log->user_id = $operator;
        $log->action = self::TASK_LOG_ACTION_UNARCHIVED;
        $log->change = '';
        $log->created_time = time();
        $log->save();
        
        return false !== $unarchived;
    }

    public function markDone($id, $operator, $isAuto = false)
    {
        $task = $this->getTask($id, ['id', 'kanban_id', 'is_finished']);

        if (!$task) {
            throw new BusinessException('task.not_found');
        }
        if (!$this->getKanbanModule()->isMember($task->kanban_id, $operator)) {
            throw new AccessDeniedException();
        }
        if ($task->is_finished == self::TASK_DONE) {
            return true;
        }
        $update = ['is_finished' => self::TASK_DONE, 'finished_time' => time(), 'updated_time' => time()];
        $updated = $this->updateTask($id, $update);

        $log = new KanbanTaskLogModel();
        $log->action = $isAuto ? self::TASK_LOG_ACTION_DONE_AUTO : self::TASK_LOG_ACTION_DONE;
        $log->kanban_id = $task->kanban_id;
        $log->task_id = $task->id;
        $log->user_id = $operator;
        $log->change = '';
        $log->created_time = time();
        $log->save();

        return $updated;
    }

    public function markUndone($id, $operator)
    {
        $task = $this->getTask($id, ['id', 'kanban_id', 'is_finished']);
        if (!$task) {
            throw new BusinessException('Task Not Found!');
        }
        if (!$this->getKanbanModule()->isMember($task->kanban_id, $operator)) {
            throw new AccessDeniedException('Access Denied!');
        }

        if ($task->is_finished == self::TASK_UNDONE) {
            return true;
        }

        $update = ['is_finished' => self::TASK_UNDONE, 'finished_time' => 0, 'updated_time' => time()];
        $updated = $this->updateTask($id, $update);

        $log = new KanbanTaskLogModel();
        $log->action = self::TASK_LOG_ACTION_UNDONE;
        $log->kanban_id = $task->kanban_id;
        $log->task_id = $task->id;
        $log->user_id = $operator;
        $log->change = '';
        $log->created_time = time();
        $log->save();

        return $updated;
    }

    public function setDate($id, array $dates, int $notifyTime, $operator)
    {
        $task = $this->getTask($id, ['id', 'kanban_id']);
        if (!$task) {
            throw new BusinessException('Task Not Found!');
        }
        if (!isset($dates['start_date']) && !isset($dates['end_date'])) {
            return true;
        }
        if (!$this->getKanbanModule()->isMember($task->kanban_id, $operator)) {
            throw new AccessDeniedException('Access Denied!');
        }
        $updateFields = ['start_time' => 0, 'end_time' => 0];
        if (!empty($dates['start_date'])) {
            $updateFields['start_time'] = strtotime($dates['start_date']);
        }
        if (!empty($dates['end_date'])) {
            $updateFields['end_time'] = strtotime($dates['end_date']);
        }
        if (!in_array($notifyTime, $this->getDueNotifyTimes())) {
            throw new InvalidParamsException('notify time error!');
        }

        $updateFields['due_notify_interval'] = $notifyTime;
        if ($notifyTime == self::TASK_DUE_NOTIFY_TIME_ON_TIME) { // 到期时通知
            $updateFields['due_notify_time'] = $updateFields['end_time'];
            $updateFields['due_notified'] = self::TASK_DUE_NOTIFY_UNSEND; // 重置后，需要重新发送到期提醒
        } elseif ($notifyTime == self::TASK_DUE_NOTIFY_TIME_NULL) { // 不通知
            $updateFields['due_notify_time'] = 0;
        } else {
            // 具体的通知时间不能小于0，且通知时间必须是未来的时间，否则不生效
            if ($updateFields['end_time'] - $notifyTime > 0 && $updateFields['end_time'] - $notifyTime > time()) {
                $updateFields['due_notify_time'] = $updateFields['end_time'] - $notifyTime;
                $updateFields['due_notified'] = self::TASK_DUE_NOTIFY_UNSEND; // 重置后，需要重新发送到期提醒
            }
        }
        
        $log = new KanbanTaskLogModel();
        $log->action = self::TASK_LOG_ACTION_SET_DUEDATE;
        $log->kanban_id = $task->kanban_id;
        $log->task_id = $task->id;
        $log->user_id = $operator;
        $log->change = date('Y-m-d', $updateFields['end_time']);
        $log->created_time = time();
        $log->save();

        return false !== $this->updateTask($id, $updateFields);
    }

    public function clearDueDate($id, $operator)
    {
        $task = $this->getTask($id, ['id', 'kanban_id']);
        if (!$task) {
            throw new BusinessException('Task Not Found!');
        }
        if (!$this->getKanbanModule()->isMember($task->kanban_id, $operator)) {
            throw new AccessDeniedException('Access Denied!');
        }

        $updateFields['due_notify_interval'] = 0; // 清除到期通知配置
        $updateFields['due_notify_time'] = 0; // 清除到期通知具体时间
        $updateFields['end_time'] = 0; // 清楚截止日期

        $log = new KanbanTaskLogModel();
        $log->action = self::TASK_LOG_ACTION_SET_DUEDATE;
        $log->kanban_id = $task->kanban_id;
        $log->task_id = $task->id;
        $log->user_id = $operator;
        $log->change = date('Y-m-d', $updateFields['end_time']);;
        $log->created_time = time();
        $log->save();

        return false !== $this->updateTask($id, $updateFields);
    }

    /**
     * 获取一个时间段内需要发送到期通知的任务.
     * 
     * @return Collection
     */
    public function getNeedDueNotifyTasksBeforeTime(int $starttime, int $endtime)
    {
        if ($starttime <= 0 || $endtime < $starttime) {
            throw new InvalidParamsException('获取到期通知的任务，时间错误: starttime %d, endtime %d', $starttime, $endtime);
        }
        $kanbanModel = new KanbanModel();
        $kanbanTable = $kanbanModel->getTable();

        $taskModel = new KanbanTaskModel();
        $taskTable = $taskModel->getTable();
        return KanbanTaskModel::leftJoin($kanbanTable, $kanbanTable.'.id', '=', $taskTable.'.kanban_id')
            ->selectRaw('"' . $kanbanTable . '.uuid as kanban_uuid, ' . $taskTable . '.*"')
            ->where($taskTable.'.due_notify_time', '<', $endtime)
            ->where($taskTable.'.due_notify_time', '>=', $starttime)
            ->where($taskTable.'.is_finished', self::TASK_UNDONE)
            ->where($taskTable.'.due_notified', self::TASK_DUE_NOTIFY_UNSEND)
            ->where($kanbanTable.'.is_closed', KanbanModule::KANBAN_NOT_CLOSED)
            ->get();
    }

    /**
     * 标记任务到期通知为已发送.
     * 
     * @param integer $id 任务id.
     * 
     * @return boolean
     */
    public function markDueNotifySended($id)
    {
        return $this->updateTask($id, ['due_notified' => self::TASK_DUE_NOTIFY_SENDED]);
    }

    public function setTitle(int $id, string $title, int $operator) : bool
    {
        $task = $this->getTask($id, ['id', 'title', 'kanban_id']);
        if (!$task) {
            throw new ResourceNotFoundException('task.not_found');
        }
       
        if (!$this->getKanbanModule()->isMember($task->kanban_id, $operator)) {
            throw new AccessDeniedException();
        }
        $updated = $this->updateTask($id, ['title' => $title]);

        $log = new KanbanTaskLogModel();
        $log->kanban_id = $task->kanban_id;
        $log->task_id = $task->id;
        $log->user_id = $operator;
        $log->action = self::TASK_LOG_ACTION_CHANGE_TITLE;
        $log->change = json_encode(['from' => $task->title, 'to' => $title]);
        $log->created_time = time();
        $log->save();

        return false !== $updated;
    }

    public function setDesc($id, $desc, $operator)
    {
        $task = $this->getTask($id, ['id', 'desc', 'kanban_id']);
        if (!$task) {
            throw new ResourceNotFoundException('task.not_found');
        }
       
        if (!$this->getKanbanModule()->isMember($task->kanban_id, $operator)) {
            throw new AccessDeniedException();
        }
        if ($task->desc == $desc) {
            return true;
        }
        $updated = $this->updateTask($id, ['desc' => $desc]);

        $log = new KanbanTaskLogModel();
        $log->kanban_id = $task->kanban_id;
        $log->task_id = $task->id;
        $log->user_id = $operator;
        $log->action = self::TASK_LOG_ACTION_CHANGE_DESC;
        $log->change = json_encode(['from' => $task->desc, 'to' => $desc]);
        $log->created_time = time();
        $log->save();

        return false !== $updated;
    }

    public function moveTaskInKanban($newListId, $taskId, $userId, $cardsSort = [])
    {
        $task = $this->getTask($taskId, ['kanban_id', 'list_id', 'is_finished']);
        if (!$task) {
            throw new ResourceNotFoundException('task.not_found');
        }
        if (!$this->getKanbanModule()->isMember($task->kanban_id, $userId)) {
            throw new AccessDeniedException();
        }
        $newList = KanbanListModel::where('id', $newListId)->first(['id', 'kanban_id', 'task_count', 'wip', 'completed']);
        if (!$newList) {
            throw new BusinessException(sprintf('List %d not found!', $newListId));
        }

        if ($task->kanban_id != $newList->kanban_id) {
            throw new BusinessException('Task and List not belong to the same kanban!');
        }

        $logger = $this->getLogger();

        $model = new KanbanTaskModel();
        if ($cardsSort) { // 同一列之内移动
            $sorted = $model->sortTasks($newListId, $cardsSort);
            if (!$sorted) {
                $logger->error('update card list failed!', func_get_args());
            }
        }

        if ($newList->id == $task->list_id) {
            return true;
        }

        if ($newList->wip != self::WIP_NO_LIMIT && $newList->wip <= $newList->task_count) {
            throw new BusinessException("task.wip.limited");
        }

        $moveToList = KanbanTaskModel::where('id', $taskId)->update(['list_id' => $newListId]);
        if (!$moveToList) {
            $logger->error('update card list failed!', func_get_args());
            return false;
        }

        KanbanListModel::where('id', $newListId)->increment('task_count');
        KanbanListModel::where('id', $task->list_id)->decrement('task_count');
        $model->sortTasks($newListId, $cardsSort);

        $log = new KanbanTaskLogModel();
        $log->action = self::TASK_LOG_ACTION_CHANGE_LIST;
        $log->kanban_id = $task->kanban_id;
        $log->task_id = $taskId;
        $log->user_id = $userId;
        $log->change = json_encode(['from' => $task->list_id, 'to' => $newListId]);
        $log->created_time = time();
        $log->save();

        // 移动到完成列就把任务标记为已完成
        if (KanbanModule::IS_COMPLETED_LIST == $newList->completed && self::TASK_UNDONE == $task->is_finished) {
            $this->markDone($taskId, $userId, true);
        }

        return true;
    }

    public function moveToOther($id, $kanbanUUid, $listId, $userId)
    {
        $task = $this->getTask($id, ['id', 'kanban_id', 'list_id', 'is_finished']);
        if (!$task) {
            throw new ResourceNotFoundException('task.not_found');
        }
        $kanban = $this->getKanbanModule()->getByUuid($kanbanUUid, ['id']);
        if (!$kanban) {
            throw new ResourceNotFoundException('kanban.not_found');
        }
        $kanbanId = $kanban->id;
        if ($task->kanban_id == $kanbanId) {
            return true;
        }
        if (!$this->getKanbanModule()->isMember($task->kanban_id, $userId) ||
            !$this->getKanbanModule()->isMember($kanbanId, $userId)
        ) {
            throw new AccessDeniedException();
        }
        if (!$this->getKanbanModule()->isListBelongKanban($listId, $kanbanId)) {
            throw new InvalidParamsException('list not belong kanban');
        }

        // 如果卡片的member，不在目标看板的member中，则需要移除卡片member关系
        $kanbanMemberIds = $this->getKanbanModule()->getMemberIds($kanbanId);
        $taskMembers = $this->getTaskMemberModule()->getTaskMembers($id, ['id', 'member_id']);
        $needRemoveMembers = [];
        foreach ($taskMembers as $taskMember) {
            if (!$kanbanMemberIds->contains($taskMember->member_id)) {
                $needRemoveMembers[] = $taskMember->member_id;
            }
        }

        // 如果卡片的label，不在目标看板的label中，则需要新建label
        $taskLabels = $this->getKanbanLabelModule()->getTaskLabels($id, ['id', 'name', 'color']);
        $toKanbanLabels = $this->getKanbanLabelModule()
            ->getKanbanLabels($kanbanId, ['id', 'name', 'color']);

        $needUpdateLabelMap = [];
        $needAddLabels = [];
        $needDelLabelIds = [];
        foreach ($taskLabels as $key => $label) {
            $isInToKanban = false;
            foreach ($toKanbanLabels as $toKanbanLabel) {
                if ($label->id != $toKanbanLabel->id && $toKanbanLabel->color == $label->color && $toKanbanLabel->name == $label->name) {
                    $needUpdateLabelMap[$label->id] = $toKanbanLabel->id;
                    $isInToKanban = true;
                    $taskLabels->forget($key);
                    break;
                }
            }
            if (!$isInToKanban) {
                $needAddLabels[] = $label;
                $needDelLabelIds[] = $label->id;
            }
        }

        $newLabels = [];
        foreach ($needAddLabels as $label) {
            $newLabel = [
                'kanban_id' => $kanbanId,
                'name' => $label->name,
                'color' => $label->color,
                'creator_id' => $userId,
                'created_time' => time(),
            ];
            $newLabels[]= $newLabel;
        }

        $newList = $this->getKanbanModule()->getList($listId);
        
        if ($newList->wip > 0 && $newList->task_count + 1 > $newList->wip) {
            throw new BusinessException('task.wip.limited');
        }

        $db = Db::connection('write');
        $db->beginTransaction();
        try {
            $this->updateTask($id, ['kanban_id' => $kanbanId, 'list_id' => $listId]);
            KanbanListModel::where('id', $task->list_id)->decrement('task_count', 1);
            KanbanListModel::where('id', $listId)->increment('task_count', 1);

            // 移动到完成列就把任务标记为已完成
            if (KanbanModule::IS_COMPLETED_LIST == $newList->completed && self::TASK_UNDONE == $task->is_finished) {
                $this->markDone($task->id, $userId, true);
            }

            // 创建新标签. TODO: 性能问题，可考虑走队列.
            $newLabelIds = [];
            foreach ($newLabels as $label) {
                $newLabelId = $this->getKanbanLabelModule()->addFromArray($label);
                $newLabelIds[] = $newLabelId;
            }

            if ($needDelLabelIds) {
                $this->getKanbanLabelModule()->rmByTaskIdAndLabelIds($id, $needDelLabelIds);
            }

            $taskLabels = [];
            foreach ($newLabelIds as $labelId) {
                $taskLabels[] = [
                    'task_id' => $id,
                    'kanban_id' => $kanbanId,
                    'label_id' => $labelId,
                    'created_time' => time()
                ];
            }
            if (!KanbanTaskLabel::insert($taskLabels)) {
                throw new BusinessException('batch insert task new label failed!');
            }

            foreach ($needUpdateLabelMap as $oldLabelId => $newLabelId) {
                $replace = $this->getKanbanLabelModule()->replaceTaskLabel($id, $oldLabelId, $newLabelId, $kanbanId);
                if (!$replace) {
                    throw new BusinessException('replace label failed!');
                }
            }

            if (!empty($needRemoveMembers)) {
                $remove = $this->getTaskMemberModule()->removeMembers($id, $needRemoveMembers);
                if (!$remove) {
                    throw new BusinessException('remove member failed!');
                }
            }

            $logData = ['from_kanban' => $task->kanban_id, 'to_kanban' => $kanbanId, 'from_list' => $task->list_id, 'to_list' => $listId];
            
            $log = new KanbanTaskLogModel();
            $log->action = self::TASK_LOG_ACTION_MOVE_TO_OTHER;
            $log->kanban_id = $task->kanban_id;
            $log->task_id = $task->id;
            $log->user_id = $userId;
            $log->change = json_encode($logData);
            $log->created_time = time();
            $log->save();

            $db->commit();

            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function copyTaskInKanban($newListId, $taskId, $userId)
    {
        $task = $this->getTask($taskId);
        if (!$task) {
            throw new ResourceNotFoundException('task.not_found');
        }

        if (!$this->getKanbanModule()->isMember($task->kanban_id, $userId)) {
            throw new AccessDeniedException();
        }

        $newList = KanbanListModel::where('id', $newListId)->first(['id', 'kanban_id', 'task_count', 'wip', 'completed']);
        if (!$newList) {
            throw new BusinessException(sprintf('List %d not found!', $newListId));
        }
        if ($task->kanban_id != $newList->kanban_id) {
            throw new BusinessException('Task and List not belong to the same kanban!');
        }
        if ($newList->wip != self::WIP_NO_LIMIT && $newList->wip <= $newList->task_count) {
            throw new BusinessException("task.wip.limited");
        }
        $taskMembers = $this->getTaskMemberModule()->getTaskMembers($taskId);

        $newTask = [
            'title' => $task->title,
            'uuid' => Uuid::uuid4()->toString(),
            'desc' => $task->desc,
            'priority' => $task->priority,
            'label_count' => $task->label_count,
            'start_time' => $task->start_time,
            'end_time' => $task->end_time,
            'due_notify_interval' => $task->due_notify_interval,
            'due_notify_time' => $task->due_notify_time,
            'due_notified' => $task->due_notified,
            'kanban_id' => $task->kanban_id,
            'is_delete' => $task->is_delete,
            'is_finished' => $task->is_finished,
            'finished_time' => $task->finished_time,
            'member_count' => count($taskMembers),
            'attachment_num' => $task->attachment_num,
            'check_list_num' => $task->check_list_num,
            'check_list_finished_num' => $task->check_list_finished_num,
            'archived' => $task->archived,
            'list_id' => $newList->id,
            'create_user' => $userId,
            'created_time' => time(),
        ];

        Db::beginTransaction();
        try {
            // 复制卡片
            $newTaskId = KanbanTaskModel::insertGetId($newTask);
            $newTask = $this->getTask($newTaskId);
            // 复制卡片成员
            $newMembers = [];
            foreach ($taskMembers as $taskMember) {
                $newMembers[] = [
                    'task_id' => $newTask->id,
                    'member_id' => $taskMember->member_id,
                    'created_time' => time()
                ];
            }
            if ($newMembers) {
                KanbanTaskMemberModel::insert($newMembers);
            }
            // 复制卡片标签
            $this->copyTaskLabel($newTask, $task);
            // 复制卡片检查项和检查项成员
            $this->copyTaskCheckList($newTask, $task, 1);
            // 复制卡片附件
            $this->copyTaskAttachment($newTask, $task);
            // 复制卡片成员
            $this->copyTaskMember($newTask, $task);
            // 更新列的卡片数量
            KanbanListModel::where('id', $newList->id)->increment('task_count');
            // 记录日志
            $log = new KanbanTaskLogModel();
            $log->kanban_id = $newTask->kanban_id;
            $log->task_id = $newTask->id;
            $log->user_id = $userId;
            $log->action = self::TASK_LOG_ACTION_CREATE;
            $log->change = json_encode(['copy_task_id' => $task->id]);
            $log->created_time = time();
            $log->save();
            // 移动到完成列就把任务标记为已完成
            if (KanbanModule::IS_COMPLETED_LIST == $newList->completed && self::TASK_UNDONE == $newTask->is_finished) {
                $this->markDone($newTask->id, $userId, true);
            }

            Db::commit();

            return true;
        } catch (\Exception $e) {
            Db::rollBack();
            $this->getLogger()->error(sprintf('failed to copy card in Kanban. error: %s, trace: %s', $e->getMessage(), $e->getTraceAsString()));

            return false;
        }
    }

    public function copyTaskToOther($newKanbanUUid, $newListId, $taskId, $userId)
    {
        $task = $this->getTask($taskId);
        if (!$task) {
            throw new ResourceNotFoundException('task.not_found');
        }
        $newKanban = $this->getKanbanModule()->getByUuid($newKanbanUUid, ['id']);
        if (!$newKanban) {
            throw new ResourceNotFoundException('kanban.not_found');
        }
        $newKanbanId = $newKanban->id;
        if ($task->kanban_id == $newKanbanId) {
            return true;
        }
        $newList = KanbanListModel::where('id', $newListId)->first(['id', 'kanban_id', 'task_count', 'wip', 'completed']);
        if (!$newList) {
            throw new ResourceNotFoundException('list.not_found');
        }
        if (!$this->getKanbanModule()->isMember($task->kanban_id, $userId) ||
            !$this->getKanbanModule()->isMember($newKanbanId, $userId)
        ) {
            throw new AccessDeniedException();
        }
        if (!$this->getKanbanModule()->isListBelongKanban($newListId, $newKanbanId)) {
            throw new InvalidParamsException('list not belong kanban');
        }
        if ($newList->wip != self::WIP_NO_LIMIT && $newList->wip <= $newList->task_count) {
            throw new BusinessException("task.wip.limited");
        }

        $newTask = [
            'title' => $task->title,
            'uuid' => Uuid::uuid4()->toString(),
            'desc' => $task->desc,
            'priority' => $task->priority,
            'label_count' => $task->label_count,
            'start_time' => $task->start_time,
            'end_time' => $task->end_time,
            'due_notify_interval' => $task->due_notify_interval,
            'due_notify_time' => $task->due_notify_time,
            'due_notified' => $task->due_notified,
            'kanban_id' => $newKanban->id,
            'is_delete' => $task->is_delete,
            'is_finished' => $task->is_finished,
            'finished_time' => $task->finished_time,
            'attachment_num' => $task->attachment_num,
            'check_list_num' => $task->check_list_num,
            'check_list_finished_num' => $task->check_list_finished_num,
            'archived' => $task->archived,
            'list_id' => $newList->id,
            'create_user' => $userId,
            'created_time' => time(),
        ];

        Db::beginTransaction();
        try {
            // 复制卡片
            $newTaskId = KanbanTaskModel::insertGetId($newTask);
            $newTask = $this->getTask($newTaskId);
            // 复制卡片标签
            $this->copyTaskLabelToOther($newTask, $task);
            // 复制卡片检查项
            $this->copyTaskCheckList($newTask, $task);
            // 复制卡片附件
            $this->copyTaskAttachment($newTask, $task);
            // 更新列的卡片数量
            KanbanListModel::where('id', $newList->id)->increment('task_count');
            // 记录日志
            $log = new KanbanTaskLogModel();
            $log->kanban_id = $newTask->kanban_id;
            $log->task_id = $newTask->id;
            $log->user_id = $userId;
            $log->action = self::TASK_LOG_ACTION_CREATE;
            $log->change = json_encode(['copy_task_id' => $task->id]);
            $log->created_time = time();
            $log->save();
            // 移动到完成列就把任务标记为已完成
            if (KanbanModule::IS_COMPLETED_LIST == $newList->completed && self::TASK_UNDONE == $newTask->is_finished) {
                $this->markDone($newTask->id, $userId, true);
            }

            Db::commit();

            return true;
        } catch (\Exception $e) {
            Db::rollBack();
            $this->getLogger()->error(sprintf('failed to copy card to other. error: %s, trace: %s', $e->getMessage(), $e->getTraceAsString()));

            return false;
        }
    }

    private function copyTaskLabelToOther($newTask, $oldTask)
    {
        $oldTaskLabels = KanbanTaskLabelModel::where('task_id', $oldTask->id)->get();
        if (!$oldTaskLabels) {
            return true;
        }

        $oldKanbanLabels = KanbanLabelModel::whereIn('id', $oldTaskLabels->pluck('label_id'))->get();
        $newKanbanLabels = KanbanLabelModel::where('kanban_id', $newTask->kanban_id)->get();
        $kanbanLabelMap = [];
        // 在新看板创建新标签
        foreach ($oldKanbanLabels as $oldKanbanLabel) {
            // 名字颜色相同的标签就不创建新的
            foreach ($newKanbanLabels as $newKanbanLabel) {
                if ($oldKanbanLabel->name == $newKanbanLabel->name &&
                    $oldKanbanLabel->color == $newKanbanLabel->color
                ) {
                    $kanbanLabelMap[$oldKanbanLabel->id] = $newKanbanLabel->id;
                    continue 2;
                }
            }
            $newKanbanLabelId = KanbanLabelModel::insertGetId([
                'kanban_id' => $newTask->kanban_id,
                'name' => $oldKanbanLabel->name,
                'color' => $oldKanbanLabel->color,
                'creator_id' => $newTask->create_user,
                'created_time' => time(),
            ]);
            $kanbanLabelMap[$oldKanbanLabel->id] = $newKanbanLabelId;
        }

        $newTaskLabels = [];
        foreach ($oldTaskLabels as $oldTaskLabel) {
            $newTaskLabels[] = [
                'task_id' => $newTask->id,
                'label_id' => $kanbanLabelMap[$oldTaskLabel->label_id],
                'kanban_id' => $newTask->kanban_id,
                'created_time' => time(),
            ];
        }

        return KanbanTaskLabelModel::insert($newTaskLabels);
    }

    private function copyTaskLabel($newTask, $oldTask)
    {
        $taskLabels = KanbanTaskLabelModel::where('task_id', $oldTask->id)->get();
        if (!$taskLabels) {
            return true;
        }

        $newTaskLabels = [];
        foreach ($taskLabels as $taskLabel) {
            $newTaskLabels[] = [
                'task_id' => $newTask->id,
                'label_id' => $taskLabel->label_id,
                'kanban_id' => $newTask->kanban_id,
                'created_time' => time(),
            ];
        }

        return KanbanTaskLabelModel::insert($newTaskLabels);
    }

    private function copyTaskCheckList($newTask, $oldTask, $copyMember = 0)
    {
        $taskCheckLists = KanbanTaskCheckListModel::where('task_id', $oldTask->id)->get();
        if (!$taskCheckLists) {
            return true;
        }

        $checkListMap = [];
        foreach ($taskCheckLists as $taskCheckList) {
            $newTaskCheckListId = KanbanTaskCheckListModel::insertGetId([
                'kanban_id' => $newTask->kanban_id,
                'task_id' => $newTask->id,
                'title' => $taskCheckList->title,
                'deleted' => $taskCheckList->deleted,
                'is_done' => $taskCheckList->is_done,
                'done_time' => $taskCheckList->done_time,
                'due_time' => $taskCheckList->due_time,
                'creator' => $newTask->create_user,
            ]);
            $checkListMap[$taskCheckList->id] = $newTaskCheckListId;
        }
        if (!$copyMember) {
            return true;
        }

        $checkListMembers = CheckListMemberModel::whereIn('check_list_id', $taskCheckLists->pluck('id'))->get();
        if (!$checkListMembers) {
            return true;
        }

        $newCheckListMembers = [];
        foreach ($checkListMembers as $checkListMember) {
            $newCheckListMembers[] = [
                'check_list_id' => $checkListMap[$checkListMember->check_list_id],
                'member_id' => $checkListMember->member_id,
                'creator_id' => $newTask->create_user,
                'created_time' => time(),
            ];
        }

        return CheckListMemberModel::insert($newCheckListMembers);
    }

    private function copyTaskAttachment($newTask, $oldTask)
    {
        $taskAttachments = KanbanTaskAttachmentModel::where('task_id', $oldTask->id)->get();
        if (!$taskAttachments) {
            return true;
        }

        $newTaskAttachments = [];
        foreach ($taskAttachments as $taskAttachment) {
            $newTaskAttachments[] = [
                'kanban_id' => $newTask->kanban_id,
                'task_id' => $newTask->id,
                'file_uri' => $taskAttachment->file_uri,
                'size' => $taskAttachment->size,
                'mine_type' => $taskAttachment->mine_type,
                'org_name' => $taskAttachment->org_name,
                'extension' => $taskAttachment->extension,
                'user_id' => $taskAttachment->user_id,
                'created_time' => time(),
            ];
        }

        return KanbanTaskAttachmentModel::insert($newTaskAttachments);
    }

    private function copyTaskMember($newTask, $oldTask)
    {
        $oldTaskMembers = KanbanTaskMemberModel::where('task_id', $oldTask->id)->get();
        if (!$oldTaskMembers) {
            return true;
        }

        $newTaskMembers = [];
        foreach ($oldTaskMembers as $oldTaskMember) {
            $newTaskMembers[] = [
                'task_id' => $newTask->id,
                'member_id' => $oldTaskMember->member_id,
                'created_time' => time(),
            ];
        }

        return KanbanTaskMemberModel::insert($newTaskMembers);
    }

    public function setPriority($id, $priority, $operator)
    {
        if (!in_array($priority, $this->getPriorities())) {
            throw new BusinessException('not support priority');
        }
        if (!$this->getKanbanModule()->isKanbanMemberByTaskId($operator, $id)) {
            throw new AccessDeniedException('Access Denied!');
        }
        $task = $this->getTask($id, ['id', 'priority', 'kanban_id']);
        if ($task->priority == $priority) {
            return true;
        }
        $this->updateTask($id, ['priority' => $priority]);

        $log = new KanbanTaskLogModel();
        $log->kanban_id = $task->kanban_id;
        $log->task_id = $id;
        $log->user_id = $operator;
        $log->action = self::TASK_LOG_ACTION_SET_PRIORITY;
        $log->change = $priority;
        $log->created_time = time();
        $log->save();

        return true;
    }

    public function getUserUndoneTaskCount($userId)
    {
        $userKanbanIds = $this->getKanbanMember()->getUserKanbans($userId, '', ['id'])->pluck('id')->toArray();
        $userKanbanIds = $userKanbanIds ?  $userKanbanIds : [-1];

        $model = new KanbanTaskModel();
        $taskMemberModel = new KanbanTaskMemberModel();
        return $model->join('kanban_task_member', $model->getTable() . '.id', '=', $taskMemberModel->getTable() . '.task_id')
            ->where($taskMemberModel->getTable() . '.member_id', '=', $userId)
            ->whereIn($model->getTable() . '.kanban_id', $userKanbanIds)
            ->where($model->getTable() . '.is_finished', self::TASK_UNDONE)
            ->where($model->getTable() . '.archived', self::TASK_NOT_ARCHIVED)
            ->count();
    }

    public function getUserUndoneTasks($userId, $sorts = [], $offset = 0, $limit = 10)
    {
        $userKanbanIds = $this->getKanbanMember()->getUserKanbans($userId, '', ['id'])->pluck('id')->toArray();
        $userKanbanIds = $userKanbanIds ?  $userKanbanIds : [-1];

        $model = new KanbanTaskModel();
        $taskMemberModel = new KanbanTaskMemberModel();
        $query = $model->join('kanban_task_member', $model->getTable() . '.id', '=', $taskMemberModel->getTable() . '.task_id')
            ->select($model->getTable() . '.*')
            ->where($taskMemberModel->getTable() . '.member_id', '=', $userId)
            ->whereIn($model->getTable() . '.kanban_id', $userKanbanIds)
            ->where($model->getTable() . '.is_finished', self::TASK_UNDONE)
            ->where($model->getTable() . '.archived', self::TASK_NOT_ARCHIVED);
        foreach ($sorts as $sort) {
            $query = $query->orderBy($sort[0], $sort[1]);
        }

        return $query->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function getPriorities()
    {
        return [
            self::PRIORITY_EMERGENCTY,
            self::PRIORITY_HIGH,
            self::PRIORITY_NORMAL,
            self::PRIORITY_LOW
        ];
    }

    public function prioritiesTxt()
    {
        return [
            self::PRIORITY_EMERGENCTY => self::PRIORITY_EMERGENCTY_TXT,
            self::PRIORITY_HIGH => self::PRIORITY_HIGH_TXT,
            self::PRIORITY_NORMAL => self::PRIORITY_NORMAL_TXT,
            self::PRIORITY_LOW => self::PRIORITY_LOW_TXT,
        ];
    }

    public function incrAttachmentNum($taskId, $num = 1)
    {
        return KanbanTaskModel::where('id', $taskId)->increment('attachment_num', $num);
    }

    public function decrAttachmentNum($taskId, $num = 1)
    {
        return KanbanTaskModel::where('id', $taskId)->decrement('attachment_num', $num);
    }

    public function incrCheckListNum($taskId, $num = 1)
    {
        return KanbanTaskModel::where('id', $taskId)->increment('check_list_num', $num);
    }

    public function decrCheckListNum($taskId, $num = 1)
    {
        return KanbanTaskModel::where('id', $taskId)->decrement('check_list_num', $num);
    }

    public function incrFinishedCheckListNum($taskId, $num = 1)
    {
        return KanbanTaskModel::where('id', $taskId)->increment('check_list_finished_num', $num);
    }

    public function decrFinishedCheckListNum($taskId, $num = 1)
    {
        return KanbanTaskModel::where('id', $taskId)->decrement('check_list_finished_num', $num);
    }

    public function updateTaskCheckListNum($taskId, $total, $finished)
    {
        $updateFields = [
            'check_list_num' => $total,
            'check_list_finished_num' => $finished
        ];
        return $this->updateTask($taskId, $updateFields);
    }

    public function setTaskMemberCount(int $taskId, int $count)
    {
        return $this->updateTask($taskId, ['member_count' => $count]);
    }

    public function waveTaskMemberCount(int $taskId, int $wave)
    {
        if ($wave < 0) {
            return KanbanTaskModel::where('id', $taskId)->decrement('member_count', abs($wave));
        }
        return KanbanTaskModel::where('id', $taskId)->increment('member_count', $wave);
    }

    public function setTaskLabelCount(int $taskId, int $count)
    {
        return $this->updateTask($taskId, ['label_count' => $count]);
    }

    public function waveTaskLabelCount(int $taskId, int $wave)
    {
        if ($wave < 0) {
            return KanbanTaskModel::where('id', $taskId)->decrement('label_count', abs($wave));
        }
        return KanbanTaskModel::where('id', $taskId)->increment('label_count', $wave);
    }

    public function getKanbanArchivedTasks($kanbanId, $fields = ['*'])
    {
        return KanbanTaskModel::where('kanban_id', $kanbanId)->where('archived', self::TASK_ARCHIVED)->get($fields);
    }

    /**
     * @return Kanban
     */
    protected function getKanbanModule()
    {
        return Kanban::inst();
    }

}
