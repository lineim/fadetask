<?php

namespace app\queue\redis\due_notify;

use app\common\mailer\Factory;
use app\common\toolkit\ModuleTrait;
use app\module\Notification;
use Webman\RedisQueue\Consumer;
use support\bootstrap\Log;

class DueNotifyConsumer implements Consumer
{
    use ModuleTrait;
    // 要消费的队列名
    public $queue = 'send_due_notify';

    // 连接名，对应 config/redis_queue.php 里的连接`
    public $connection = 'default';

    // 消费
    public function consume($data)
    {
        $logger = Log::channel('mailer');
        $id = $data['id'] ?? 0;
        $tos = $data['tos'] ?? [];
        $subject = $data['subject'] ?? '';
        $version = $data['mail_version'] ?? '2.0';
        if (empty($tos)) {
            $logger->error('send due mail failed, tos empty', $data);
            return;
        }
        if ($version != '2.0') {
            $logger->error('send due mail failed, params version must be 2.0', $data);
            return;
        }
        $task = $this->getKanbanTaskModule()->getTask($id, ['due_notified', 'kanban_id', 'end_time']);
        if (!$task) {
            $logger->error('send due mail failed, task not found', $data);
            return;
        }
        if ($task->due_notified) {
            $logger->debug('send due mail skipped, had notified!');
            return;
        }

        // 站内信通知
        $logger->debug('insert inner site due notifiction', $data);
        try {
            $userIds = $data['userIds'];
            $params = [
                'url' => $data['url'],
                'title' => $data['title'],
                'kanban_id' => $task->kanban_id,
                'kanban_uuid' => $data['kanban_uuid'],
                'task_id' => $id,
                'due_time' => $data['due_time']
            ];
            $this->getNotificationModule()->batchNew($userIds, Notification::TPL_TASK_DUE_NOTIFY, $params);
        } catch (\Exception $e) {
            $logger->error(sprintf('insert inner site due notifiction error: %s', $e->getMessage()), ['params' => $data, 'trace' => $e->getTrace()]);
            throw $e;
        }

        // 邮件通知
        $logger->debug('send due mail', $data);
        try {
            $htmlContent = render('notification/due_notify', $data);
            $mailer = Factory::mailer();
            $from = [config('mailer.smtp.user') => config('mailer.smtp.username')];
            $mailer->batchSend($from, $tos, $subject, $htmlContent);
            $this->getKanbanTaskModule()->markDueNotifySended($id);
        } catch (\Exception $e) {
            $logger->error(sprintf('send due mail error: %s', $e->getMessage()), ['params' => $data, 'trace' => $e->getTrace()]);
            throw $e;
        }
    }
}