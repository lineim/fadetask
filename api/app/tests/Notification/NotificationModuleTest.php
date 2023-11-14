<?php
namespace app\tests\Notification;

use app\common\exception\BusinessException;
use app\module\Notification;
use app\tests\Base;
use app\tests\Common\DataGenerater;

class NotificationModuleTest extends Base
{

    public function testNewWithErrorTpl()
    {
        $userId = DataGenerater::userGenerater();
        $module = $this->getNotificationModule();

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('notifaction.error.tpl_not_support');

        $module->newNotification($userId, 'not support tpl code', []);
    }

    public function testNewNotification()
    {
        $userId = DataGenerater::userGenerater();
        $module = $this->getNotificationModule();

        $params = ['id' => 1, 'code' => 'ddkks', 'url' => 'https://lineim.com/?query=ksks'];

        $id = $module->newNotification($userId, 'notification.join_kanban', $params);
        $this->assertEquals(1, $id);
        $n = $module->getNotificationById($id);
        $this->assertEquals('notification.join_kanban', $n->template);
        $this->assertEquals($userId, $n->user_id);
        $this->assertTrue($n->created_time > 0);
        $this->assertEquals(0, $n->readed);
        $this->assertEquals(json_encode($params), $n->params);
    }

    public function testBatchNew()
    {
        $userIds[] = DataGenerater::userGenerater();
        $userIds[] = DataGenerater::userGenerater(['uuid' => 'unittestuuid2', 'email' => 'unittes2t@example.com']);
        $userIds[] = DataGenerater::userGenerater(['uuid' => 'unittestuuid3', 'email' => 'unittes3t@example.com']);
        $userIds[] = DataGenerater::userGenerater(['uuid' => 'unittestuuid4', 'email' => 'unittes4t@example.com']);
        $userIds[] = DataGenerater::userGenerater(['uuid' => 'unittestuuid5', 'email' => 'unittes5t@example.com']);
        $userIds[] = DataGenerater::userGenerater(['uuid' => 'unittestuuid6', 'email' => 'unittes6t@example.com']);
        $module = $this->getNotificationModule();

        $params = ['id' => 1, 'code' => 'ddkks', 'url' => 'https://lineim.com/?query=ksks'];

        $batchInsert = $module->batchNew($userIds,Notification::TPL_JOIN_TASK, $params);
        $this->assertTrue($batchInsert);
    }

    public function testReaded()
    {
        $userId = DataGenerater::userGenerater();
        $module = $this->getNotificationModule();

        $params = ['id' => 1, 'code' => 'ddkks', 'url' => 'https://lineim.com/?query=ksks'];

        $id = $module->newNotification($userId, 'notification.join_kanban', $params);
        $this->assertEquals(1, $id);
        $n = $module->getNotificationById($id);
        $this->assertEquals(0, $n->readed);

        $row = $module->readed($id, $userId);
        $this->assertEquals(1, $row);
        $n = $module->getNotificationById($id);
        $this->assertEquals(1, $n->readed);
    }

    public function testBatchReaded()
    {
        $userId = DataGenerater::userGenerater();
        $module = $this->getNotificationModule();

        $module = $this->getNotificationModule();

        $params = ['id' => 1, 'code' => 'ddkks', 'url' => 'https://lineim.com/?query=ksks'];
        for ($i = 0; $i < 55; $i ++) {
            $tpls = $module->getSupportTpl();
            shuffle($tpls);
            $tpl = array_pop($tpls);
            $module->newNotification($userId, $tpl, $params);
        }
        $notifications = $module->getUserNotifications($userId, Notification::UNREAD, 0, 10);
        $this->assertCount(10, $notifications);
        $ids = [];
        foreach ($notifications as $n) {
            $ids[] = $n->id;
        }
        $batchReaded = $module->batchReaded($ids, $userId);
        $this->assertEquals(10, $batchReaded);
        foreach ($ids as $id) {
            $n = $module->getNotificationById($id);
            $this->assertEquals(Notification::READED, $n->readed);
        }
    }


    public function testGetUserNotification()
    {
        $userId = DataGenerater::userGenerater();
        $userId2 = DataGenerater::userGenerater(['uuid' => 'unittestuuid2', 'email' => 'unittes2t@example.com']);
        $module = $this->getNotificationModule();

        $params = ['id' => 1, 'code' => 'ddkks', 'url' => 'https://lineim.com/?query=ksks'];
        for ($i = 0; $i < 55; $i ++) {
            $tpls = $module->getSupportTpl();
            shuffle($tpls);
            $tpl = array_pop($tpls);
            $module->newNotification($userId, $tpl, $params);
        }
        $notifications = $module->getUserNotifications($userId, Notification::UNREAD, 0, 10);
        $this->assertCount(10, $notifications);

        $notifications = $module->getUserNotifications($userId, Notification::UNREAD, 50, 10);
        $this->assertCount(5, $notifications);

        $preId = 56;
        foreach ($notifications as $n) {
            $this->assertEquals(Notification::UNREAD, $n->readed);
            $this->assertEquals($userId, $n->user_id);
            $this->assertTrue($preId > $n->id); // 测试倒序
            $preId = $n->id;
        }
    }

    public function testHasUnReadNotifications()
    {
        $userId = DataGenerater::userGenerater();
        $no = $this->getNotificationModule()->hasUnReadNotifications($userId);
        $this->assertFalse($no);

        $params = ['id' => 1, 'code' => 'ddkks', 'url' => 'https://lineim.com/?query=ksks'];
        $id = $this->getNotificationModule()->newNotification($userId, 'notification.join_kanban', $params);
        $yes = $this->getNotificationModule()->hasUnReadNotifications($userId);
        $this->assertTrue($yes);
        $this->getNotificationModule()->readed($id, $userId);
        $no = $this->getNotificationModule()->hasUnReadNotifications($userId);
        $this->assertFalse($no);
    }

    public function testMarkUserNotificationsReaded()
    {
        $userId = DataGenerater::userGenerater();
        $userId2 = DataGenerater::userGenerater(['uuid' => 'unittestuuid2', 'email' => 'unittes2t@example.com']);
        $module = $this->getNotificationModule();

        $params = ['id' => 1, 'code' => 'ddkks', 'url' => 'https://lineim.com/?query=ksks'];
        for ($i = 0; $i < 55; $i ++) {
            $tpls = $module->getSupportTpl();
            shuffle($tpls);
            $tpl = array_pop($tpls);
            $module->newNotification($userId, $tpl, $params);
            $module->newNotification($userId2, $tpl, $params);
        }
        $yes = $module->hasUnReadNotifications($userId);
        $this->assertTrue($yes);
        $module->markUserNotificationsReaded($userId);
        $no = $module->hasUnReadNotifications($userId);
        $this->assertFalse($no);
        $yes = $module->hasUnReadNotifications($userId2);
        $this->assertTrue($yes);
    }

}
