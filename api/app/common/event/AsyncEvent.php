<?php
namespace app\common\event;

use support\bootstrap\Container;
use Webman\RedisQueue\Client;

class AsyncEvent 
{
    /**
     * 发送异步事件，这里有两个异步，第一个是Client::send是异步投递的，见
     * https://www.workerman.net/plugin/12;
     * 第二个是投递出去的事件是异步处理的.
     * 
     * @param string $event 事件名，投递到队列中就是队列名称.
     * @param mixed  $data  事件数据.
     * @param int    $delay 延迟处理时间，单位秒.
     * 
     * @return void
     */
    public function emit(string $event, $data, int $delay = 0)
    {
        Client::send($event, $data, $delay);
    }

    public static function start($worker)
    {
        Container::set('async.event', new self());
    }

}
