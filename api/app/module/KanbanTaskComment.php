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
use app\common\exception\ResourceNotFoundException;
use app\model\KanbanTaskLog as KanbanTaskLogModel;

class KanbanTaskComment extends BaseModule
{
    const DELETED = 1;
    const UN_DELETED = 0;

    public function getTaskComments(
        $taskId, 
        array $fields = ['id', 'kanban_id', 'task_id', 'action', 'change', 'user_id', 'created_time'], 
        $start = 0, 
        $limit = 10
    )
    {
        return KanbanTaskLogModel::where('task_id', $taskId)
            ->where('is_delete', self::UN_DELETED)
            ->orderBy('id', 'DESC')
            ->offset($start)
            ->limit($limit)
            ->get($fields);
    }

    public function getTaskCommentCount($taskId)
    {
        return KanbanTaskLogModel::where('task_id', $taskId)
            ->where('is_delete', self::UN_DELETED)
            ->count();
    }

    public function getCommentById($id, array $fields = ['*'])
    {
        return KanbanTaskLogModel::where('id', $id)->first($fields);
    }

    public function comment($taskId, $content, $userId, $parentId = 0)
    {
        if (!$this->getKanbanMember()->isKanbanMemberByTaskId($userId, $taskId)) {
            throw new AccessDeniedException();
        }
        $task = $this->getKanbanTaskModule()->getTask($taskId, ['kanban_id']);

        $comment = new KanbanTaskLogModel();
        $comment->kanban_id = $task->kanban_id;
        $comment->task_id = $taskId;
        $comment->user_id = $userId;
        $comment->change = $content;
        $comment->action = KanbanTask::TASK_LOG_ACTION_ADD_COMMENT;
        $comment->created_time = time();
        $comment->save();

        $this->getKanbanTaskModule()->waveTaskCommentNum($taskId, 1);

        return $comment;
    }

    protected function updateComment($id, array $fields)
    {
        return KanbanTaskLogModel::where('id', $id)->update($fields);
    }

    public function updateContent($id, $content, $userId)
    {
        $comment = $this->getCommentById($id, ['user_id', 'task_id', 'kanban_id', 'change']);
        if (!$comment) {
            throw new ResourceNotFoundException('Resource not found!');
        }
        if (!$this->getKanbanMember()->isKanbanMemberByTaskId($userId, $comment->task_id)) {
            throw new AccessDeniedException();
        }
        if ($comment->user_id !== $userId) {
            throw new AccessDeniedException();
        }
        $update = $this->updateComment($id, ['change' => $content, 'updated_time' => time()]);

        return $update;
    }

    public function deleteComment($id, $userId)
    {
        $comment = $this->getCommentById($id, ['user_id', 'task_id', 'kanban_id', 'change']);
        if (!$comment) {
            throw new ResourceNotFoundException('Resource Not Found!');
        }
        if (!$this->getKanbanMember()->isKanbanMemberByTaskId($userId, $comment->task_id)) {
            throw new AccessDeniedException();
        }
        if ($comment->user_id !== $userId) {
            throw new AccessDeniedException();
        }
        if ($deleted = $this->updateComment($id, ['is_delete' => self::DELETED])) {
            $this->getKanbanTaskModule()->waveTaskCommentNum($comment->task_id, -1);
        }

        // $log = new KanbanTaskLogModel();
        // $log->kanban_id = $comment->kanban_id;
        // $log->task_id = $comment->task_id;
        // $log->user_id = $userId;
        // $log->change = $comment->content;
        // $log->action = KanbanTask::TASK_LOG_ACTION_DEL_COMMENT;
        // $log->created_time = time();
        // $log->save();

        return $deleted;
    }

}
