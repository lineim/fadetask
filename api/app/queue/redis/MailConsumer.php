<?php

namespace app\queue\redis;

use app\common\mailer\Factory;
use Webman\RedisQueue\Consumer;
use support\bootstrap\Log;

class MailConsumer implements Consumer
{
    // 要消费的队列名
    public $queue = 'send_mail';

    // 连接名，对应 config/redis_queue.php 里的连接`
    public $connection = 'default';

    // 消费
    public function consume($data)
    {
        $to = $data['to'];
        $subject = $data['subject'];
        $content = $data['content'];

        $logger = Log::channel('mailer');
        $logger->debug('send mail', $data);

        try {
            $mailer = Factory::mailer();
            $from = [config('mailer.smtp.user') => config('mailer.smtp.username')];
            $mailer->singleSend($from, $to, $subject, $content);
        } catch (\Exception $e) {
            unset($data['content']);
            $logger->error(sprintf('send maill error: %s', $e->getMessage()), ['params' => $data, 'trace' => $e->getTrace()]);
            throw $e;
        }
    }
}