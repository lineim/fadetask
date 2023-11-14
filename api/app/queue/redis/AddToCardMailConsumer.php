<?php

namespace app\queue\redis;

use app\common\mailer\Factory;
use app\common\toolkit\ModuleTrait;
use app\module\Notification;
use Webman\RedisQueue\Consumer;
use support\bootstrap\Log;

class AddToCardMailConsumer implements Consumer
{
    use ModuleTrait;
    // 要消费的队列名
    public $queue = 'join_task_notify';

    // 连接名，对应 config/redis_queue.php 里的连接`
    public $connection = 'default';

    // 消费
    public function consume($data)
    {
        $logger = Log::channel();
        $tos = $data['tos'] ?? [];
        $task = $data['task'] ?? [];
        $operator = $data['operator'] ?? [];

        $version = $data['mail_version'] ?? '2.0';
        if (empty($tos)) {
            $logger->error('send join card mail failed, tos empty', $data);
            return;
        }

        if ($version != '2.0') {
            $logger->error('send join card mail failed, params version must be 2.0', $data);
            return;
        }
        if (empty($task) || empty($operator)) {
            $logger->error('send join card mail failed, task or operator empty', $data);
            return;
        }

        // 邮件通知
        $logger->debug('send join card mail', $data);
        try {
            $kanbanUrl = config('app.kanban_url');
            $params = [
                'operator' => $operator['name'] ?? '',
                'title' => $task['title'] ?? '',
                'url' => str_replace('{id}', $task['kanban_uuid'], $kanbanUrl) . '?' . http_build_query(['card_id' => $task['id']]),
            ];
            $htmlContent = render('notification/join_card', $params);
            $mailer = Factory::mailer();
            $from = [config('mailer.smtp.user') => config('mailer.smtp.username')];
            $mailer->batchSend($from, $tos, '加入卡片通知', $htmlContent);
        } catch (\Exception $e) {
            $logger->error(sprintf('send join card mail error: %s', $e->getMessage()), ['params' => $data, 'trace' => $e->getTrace()]);
            throw $e;
        }
    }
}