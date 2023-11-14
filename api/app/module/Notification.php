<?php
/**
 * This file is part of fade task project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\module;

use app\common\exception\BusinessException;
use app\common\exception\InvalidParamsException;
use app\model\Notification as NotifyModel;

/**
 * 站内信.
 */
class Notification extends BaseModule
{

    const UNREAD = 0;
    const READED = 1;

    const TPL_JOIN_KANBAN = 'notification.join_kanban';
    const TPL_JOIN_TASK = 'notification.join_task';
    const TPL_TASK_DUE_NOTIFY = 'notification.task_due_notify';
    const TPL_TASK_MENTION = 'notification.task_mention';
    const TPL_JOIN_CHECKLIST = 'notification.join_checklist';
    const TPL_RM_FROM_CHECKLIST = 'notification.rm_from_checklist';


    public function newNotification(int $userId, string $tpl, array $params)
    {
        if (!in_array($tpl, $this->getSupportTpl())) {
            throw new BusinessException('notifaction.error.tpl_not_support');
        }
        $notification = [
            'user_id' => $userId,
            'template' => $tpl,
            'params' => json_encode($params),
            'created_time' => time()
        ];
        return NotifyModel::insertGetId($notification);
    }

    public function batchNew(array $userIds, string $tpl, array $params)
    {
        if (!in_array($tpl, $this->getSupportTpl())) {
            throw new BusinessException('notifaction.error.tpl_not_support');
        }
        $notifications = [];
        foreach($userIds as $userId) {
            $notifications[] = [
                'user_id' => $userId,
                'template' => $tpl,
                'created_time' => time(),
                'params' => json_encode($params)
            ];
        }
        if (count($notifications) > 0) {
            return NotifyModel::insert($notifications);
        }
        return false;
    }

    public function joinTaskNotification($userId, array $taskInfo, array $operator)
    {
        $taskRequiredFields = ['id', 'title'];
        $userRequiredFields = ['id', 'name'];

        if (array_diff($taskRequiredFields, array_keys($taskInfo))) {
            throw new InvalidParamsException('error.params.error');
        }

        if (array_diff($userRequiredFields, array_keys($operator))) {
            throw new InvalidParamsException('error.params.error');
        }

        $notification = [
            'user_id' => $userId,
            'template' => self::TPL_JOIN_TASK,
            'params' => json_encode(['operator' => $operator, 'task' => $taskInfo], true),
            'created_time' => time()
        ];

        return NotifyModel::insertGetId($notification);
    }

    public function joinCheckListNotification($userId, $type, array $params, array $operator)
    {
        $requiredParams = ['task_id', 'check_list_id', 'task_title', 'check_list_title'];
        $userRequiredFields = ['id', 'name'];

        if (array_diff($requiredParams, array_keys($params))) {
            throw new InvalidParamsException('error.params.error');
        }

        if (array_diff($userRequiredFields, array_keys($operator))) {
            throw new InvalidParamsException('error.params.error');
        }
        $notification = [
            'user_id' => $userId,
            'template' => $type == 'add' ? self::TPL_JOIN_CHECKLIST : self::TPL_RM_FROM_CHECKLIST,
            'params' => json_encode(['operator' => $operator, 'params' => $params], true),
            'created_time' => $params['time']
        ];

        return NotifyModel::insertGetId($notification);
    }

    public function getNotificationById($id, array $fields = ['*'])
    {
        return NotifyModel::where('id', $id)->first($fields);
    }

    public function readed($id, $userId)
    {
        return NotifyModel::where('id', $id)
            ->where('user_id', $userId)
            ->update(['readed' => self::READED]);
    }

    public function batchReaded(array $ids, $userId)
    {
        return NotifyModel::whereIn('id', $ids)
            ->where('user_id', $userId)
            ->update(['readed' => self::READED]);
    }

    public function batchReadedByUserId($userId)
    {
        return NotifyModel::where('user_id', $userId)
            ->update(['readed' => self::READED]);
    }

    public function markUserNotificationsReaded($userId)
    {
        return NotifyModel::where('user_id', $userId)
            ->update(['readed' => self::READED]);
    }

    public function hasUnReadNotifications($userId)
    {
        return NotifyModel::where('user_id', $userId)
            ->where('readed', self::UNREAD)
            ->exists();
    }

    public function getUserNotifications($userId, $readed = self::UNREAD, $start = 0, $limit = 10, $fields = ['*'])
    {
        $model = NotifyModel::where('user_id', $userId);
        if (in_array($readed, [self::READED, self::UNREAD])) {
            $model->where('readed', $readed);
        }
           
        return $model->orderBy('id', 'desc')
            ->offset($start)
            ->limit($limit)
            ->get($fields);
    }

    public function getSupportTpl()
    {
        return [
            self::TPL_JOIN_KANBAN,
            self::TPL_JOIN_TASK,
            self::TPL_TASK_DUE_NOTIFY,
            self::TPL_TASK_MENTION,
            self::TPL_JOIN_CHECKLIST,
            self::TPL_RM_FROM_CHECKLIST,
        ];
    }

}
