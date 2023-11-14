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
use app\common\toolkit\DateTime;
use support\Request;

class TaskComment extends Base
{

    public function get(Request $request, $taskId)
    {
        $user = $this->getUser($request);
        if (!$this->isKanbanMemberByTaskId($taskId, $user['id'])) {
            throw new AccessDeniedException("Access Denied!");
        }
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 5);
        $fields = ['id', 'kanban_id', 'task_id', 'action', 'change', 'user_id', 'created_time'];
        $comments = $this->getTaskCommentModule()->getTaskComments($taskId, $fields, ($page - 1)*$pageSize, $pageSize);

        if (!$comments->isEmpty()) {
            $userIds = $comments->pluck('user_id')->toArray();
            $tmpUsers = $this->getUserModule()->getByUserIds($userIds, ['id', 'uuid', 'name', 'avatar']);
            $users = [];
            foreach ($tmpUsers as $user) {
                $users[$user->id] = $user; 
            }
            unset($tmpUsers);

            foreach ($comments as &$comment) {
                $user = $users[$comment->user_id] ?? [];
                $comment->user = $user;
                $comment->create_date = DateTime::sampleDate($comment->create_time);
            }
        }
        $total = $this->getTaskCommentModule()->getTaskCommentCount($taskId);

        return $this->json(['total' => $total, 'comments' => $comments]);
    }

    public function add(Request $request, $taskId)
    {
        $user = $this->getUser($request);
        $content = $request->post('content', '');

        $comment = $this->getTaskCommentModule()->comment($taskId, $content, $user['id']);
        $comment->user = $user;

        return $this->json($comment);
    }

    public function edit(Request $request, $taskId, $id)
    {
        $user = $this->getUser($request);
        $content = $request->post('content', '');

        $this->getTaskCommentModule()->updateContent($id, $content, $user['id']);

        return $this->json(true);
    }

    public function delete(Request $request, $taskId, $id)
    {
        $user = $this->getUser($request);
        $this->getTaskCommentModule()->deleteComment($id, $user['id']);

        return $this->json(true);
    }

}