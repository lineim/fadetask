<?php

namespace app\queue\redis;

use app\common\sms\Factory;
use app\common\toolkit\Validator;
use Webman\RedisQueue\Consumer;
use support\bootstrap\Log;

class SmsConsumer implements Consumer
{
    // 要消费的队列名
    public $queue = 'send_sms';

    // 连接名，对应 config/redis_queue.php 里的连接`
    public $connection = 'default';

    // 消费
    public function consume($data)
    {
        $logger = Log::channel('sms');
        $logger->debug('send sms', $data);
        $to = $data['to'];
        if (!Validator::mobile($to)) {
            $logger->error('send sms error, mobile empty');
            return;
        }
        $tplCode = $data['tplCode'];
        unset($data['to'], $data['tplCode']);

        try {
            $sender = Factory::sms();
            $sender->send($to, $tplCode, $data);
        } catch (\Exception $e) {
            $logger->error(sprintf('send sms error: %s', $e->getMessage()), ['params' => $data, 'trace' => $e->getTrace()]);
            return false;
        }
    }
}