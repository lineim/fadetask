<?php
namespace app\module\Notification\Bridge;

use Webman\RedisQueue\Client;

class Queue implements SenderBridgeInterface
{

    protected $queue = '';

    public function setQueue($queue)
    {
        $this->queue = $queue;
    }

    public function send($data)
    {
        $delay = 0;
        if (isset($data['delay_send'])) {
            $delay = (int) $data['delay_send'];
        }
        unset($data['delay_send']);
        Client::send($this->queue, $data, $delay);
        return true;
    }

}

