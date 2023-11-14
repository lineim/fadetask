<?php
namespace app\tests\Notification;

use app\common\exception\BusinessException;
use app\module\Notification\Factory;
use app\module\Notification\NotificationInterface;
use app\tests\Base;

class FactoryTest extends Base
{

    public function testNotSupportChannel()
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('notification channel weibo not support');
        Factory::getChannel('weibo');
    }

    public function testSupportChannelButNotImplement()
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('notification channel class app\\module\\Notification\\Feishu not found');
        Factory::getChannel('feishu');
    }

    public function testNoSupportBridge()
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('notification bridge mysql not support');
        Factory::getChannel('mail', 'mysql');
    }

    public function testEmptyQueueName()
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('bridge queue need queue name');
        Factory::getChannel('mail', 'queue', '');
    }

    public function testGetMailChannelSuccess()
    {
        $channel = Factory::getChannel('mail', 'queue', 'send_mail');
        $this->assertInstanceOf(NotificationInterface::class, $channel);
    }


}
