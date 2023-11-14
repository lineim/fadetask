<?php
namespace app\tests\Notification;

use app\common\exception\BusinessException;
use app\tests\Base;
use app\module\Notification\Factory;
use app\module\Notification\Bridge\Queue;

class MailTest extends Base
{

    public function testSendRegVerifyCodeSuccess()
    {
        $bridgeStub = $this->createMock(Queue::class);
        $bridgeStub->method('setQueue')->willReturn('');
        $bridgeStub->method('send')->willReturn(true);
        $channel = Factory::getChannel('mail', 'queue', 'send_mail');
        $channel->setSenderBridge($bridgeStub);
        $this->assertTrue($channel->sendRegVerifyCode('test@test.com', '123456'));
    }

    public function testSendLoginVerifyCodeSuccess()
    {
        $bridgeStub = $this->createMock(Queue::class);
        $bridgeStub->method('setQueue')->willReturn('');
        $bridgeStub->method('send')->willReturn(true);
        $channel = Factory::getChannel('mail', 'queue', 'send_mail');
        $channel->setSenderBridge($bridgeStub);
        $this->assertFalse($channel->sendLoginVerifyCode('test@test.com', '123456'));
    }

    public function testTplNotFound()
    {
        $bridgeStub = $this->createMock(Queue::class);
        $bridgeStub->method('setQueue')->willReturn('');
        $bridgeStub->method('send')->willReturn(true);
        $channel = Factory::getChannel('mail', 'queue', 'send_mail');
        $channel->setSenderBridge($bridgeStub);
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('notifaction template not found, channel mail, type notfoundtpl');
        $channel->sendSingleMsg('test@test.com', 'notfoundtpl', []);
    }

    public function testBatchSend()
    {
        $bridgeStub = $this->createMock(Queue::class);
        $bridgeStub->method('setQueue')->willReturn('');
        $bridgeStub->method('send')->willReturn(true);
        $channel = Factory::getChannel('mail', 'queue', 'send_mail');
        $channel->setSenderBridge($bridgeStub);
        $this->assertFalse($channel->sendLoginVerifyCode('test@test.com', '123456'));
    }

}
