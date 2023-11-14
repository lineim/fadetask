<?php
namespace app\tests\Notification;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\module\Kanban;
use app\module\KanbanMember;
use app\tests\Base;
use app\module\Notification\Factory;
use app\module\Notification\Bridge\Queue;
use app\tests\Common\DataGenerater;

class KanbanMemberTest extends Base
{
    public function testIsMembers()
    {
        $userId = DataGenerater::userGenerater([]);
        $kanban = $this->getKanbanModule()->create('UnitTest Kanban', 'A kanban for unit test!', $userId);
        $newKanbanId = $kanban->id;
        $userId = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid1',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223']);
        $this->getKanbanModule()->joinKanban($newKanbanId, $userId, Kanban::MEMBER_ROLE_ADMIN);
        $userId2 = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid2',
            'email' => 'unittest3@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest2',
            'mobile' => '18011112224']);
        $isMembers = $this->getKanbanModule()->isMembers($newKanbanId, [$userId, $userId2]);
        $this->assertFalse($isMembers);
        $this->getKanbanModule()->joinKanban($newKanbanId, $userId2, Kanban::MEMBER_ROLE_ADMIN);
        $isMembers = $this->getKanbanModule()->isMembers($newKanbanId, [$userId, $userId2]);
        $this->assertTrue($isMembers);
    }

    public function testIsMember()
    {
        $userId = DataGenerater::userGenerater([]);
        $kanban = $this->getKanbanModule()->create('UnitTest Kanban', 'A kanban for unit test!', $userId);
        $newKanbanId = $kanban->id;
        $userId = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid1',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223']);

        $this->assertFalse($this->getKanbanModule()->isMember($newKanbanId, $userId));
        $this->getKanbanModule()->joinKanban($newKanbanId, $userId, Kanban::MEMBER_ROLE_ADMIN);
        $this->assertTrue($this->getKanbanModule()->isMember($newKanbanId, $userId));
    }

    public function testSetMemberAsAdmin()
    {
        $userId = DataGenerater::userGenerater([]);
        $kanban = $this->getKanbanModule()->create('UnitTest Kanban', 'A kanban for unit test!', $userId);
        $newKanbanId = $kanban->id;
        $userRole = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid3',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest3',
            'mobile' => '18011112224']
        );
        $this->getKanbanMemberModule()->joinKanban($newKanbanId, $userRole, KanbanMember::MEMBER_ROLE_USER);
        $this->getKanbanModule()->setMemberAsAdmin($newKanbanId, $userRole, $userId);
        $this->assertTrue($this->getKanbanMemberModule()->isAdmin($newKanbanId, $userRole));
        $rmPermission = $this->getKanbanMemberModule()->removeMemberAdminPermission($newKanbanId, $userRole, $userRole);
        $this->assertTrue($rmPermission);
        $rmMember = $this->getKanbanMemberModule()->removeMember($newKanbanId, $userRole, $userId);
        $this->assertTrue($rmMember);
    }

    public function testIsAdmin()
    {
        $ownerId = DataGenerater::userGenerater([]);
        $kanban = $this->getKanbanModule()->create('UnitTest Kanban', 'A kanban for unit test!', $ownerId);
        $kanbanId = $kanban->id;
        $this->assertTrue($this->getKanbanMember()->isAdmin($kanbanId, $ownerId));
        $userSysAdmin = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid2',
            'email' => 'unittest2@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'role' => 'ADMIN',
            'name' => 'unittest1',
            'mobile' => '18011112223']
        );
        $this->assertTrue($this->getKanbanMember()->isAdmin($kanbanId, $userSysAdmin));
        
        $userRole = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid3',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest3',
            'mobile' => '18011112224']
        );
        $this->assertFalse($this->getKanbanMember()->isAdmin($kanbanId, $userRole));
        $this->getKanbanMemberModule()->joinKanban($kanbanId, $userRole, KanbanMember::MEMBER_ROLE_USER);
        $this->assertFalse($this->getKanbanMember()->isAdmin($kanbanId, $userRole));
        $this->getKanbanMemberModule()->setMemberAsAdmin($kanbanId, $userRole, $ownerId);

        $this->assertTrue($this->getKanbanMember()->isAdmin($kanbanId, $userRole));
    }

    public function testDeleteKanbanMemberDeleteOwner()
    {
        $ownerId = DataGenerater::userGenerater([]);
        $kanbanId = $this->getKanbanModule()->create('UnitTest Kanban1', 'A kanban for unit test!', $ownerId);

        $this->expectException(AccessDeniedException::class);
        $this->getKanbanMemberModule()->deleteKanbanMember($kanbanId, $ownerId, $ownerId);
    }

    public function testDeleteKanbanMemberWithOutPermission()
    {
        $ownerId = DataGenerater::userGenerater([]);
        $kanban = $this->getKanbanModule()->create('UnitTest Kanban', 'A kanban for unit test!', $ownerId);
        $kanbanId = $kanban->id;

        $userId = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid3',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest3',
            'mobile' => '18011112224']
        );
        $this->getKanbanMemberModule()->joinKanban($kanbanId, $userId, KanbanMember::MEMBER_ROLE_USER);
        $this->expectException(AccessDeniedException::class);
        $this->getKanbanMemberModule()->deleteKanbanMember($kanbanId, $userId, $userId);
    }

    public function testDeleteKanbanMemberWithMemberNotFound()
    {
        $ownerId = DataGenerater::userGenerater([]);
        $kanban = $this->getKanbanModule()->create('UnitTest Kanban', 'A kanban for unit test!', $ownerId);
        $kanbanId = $kanban->id;

        $userId = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid3',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest3',
            'mobile' => '18011112224']
        );
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('kanban.member.msg_not_found');
        $this->getKanbanMemberModule()->deleteKanbanMember($kanbanId, $userId, $ownerId);
    }

    public function testDeleteKanbanMember()
    {
        $ownerId = DataGenerater::userGenerater([]);
        $kanban = $this->getKanbanModule()->create('UnitTest Kanban', 'A kanban for unit test!', $ownerId);
        $kanbanId = $kanban->id;

        $userId = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid3',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest3',
            'mobile' => '18011112224']
        );
        $this->getKanbanMemberModule()->joinKanban($kanbanId, $userId, KanbanMember::MEMBER_ROLE_USER);

        $kanbanList = $this->getKanbanModule()->getKanbanList($kanbanId);
        $listIds = [];
        foreach ($kanbanList as $l) {
            $listIds[] = $l->id;
        }
        $task = ['title' => 'unittest', 'desc' => '', 'kanbanId' => $kanbanId];
        $listId = array_pop($listIds);
        $taskId1 = $this->getKanbanTaskModule()->createTask($task, $userId, $listId);
        $taskId2 = $this->getKanbanTaskModule()->createTask($task, $userId, $listId);
        $taskId3 = $this->getKanbanTaskModule()->createTask($task, $userId, $listId);

        $this->getKanbanTaskModule()->setMembers($taskId1, [$ownerId, $userId], $ownerId);
        $this->getKanbanTaskModule()->setMembers($taskId2, [$userId], $ownerId);
        $this->getKanbanTaskModule()->setMembers($taskId3, [$ownerId], $ownerId);

        $del = $this->getKanbanMemberModule()->deleteKanbanMember($kanbanId, $userId, $ownerId);
        $this->assertEquals(1, $del);

        $task1Members = $this->getTaskMemberModule()->getTaskMembers($taskId1);
        $this->assertCount(1, $task1Members);
        foreach ($task1Members as $m) {
            $this->assertNotEquals($userId, $m->member_id);
        }

        $task2Members = $this->getTaskMemberModule()->getTaskMembers($taskId2);
        $this->assertCount(0, $task2Members);

        $task3Members = $this->getTaskMemberModule()->getTaskMembers($taskId3);
        $this->assertCount(1, $task3Members);
        foreach ($task3Members as $m) {
            $this->assertNotEquals($userId, $m->member_id);
        }
    }

    public function testGetMembersWithUserInfo()
    {
        $ownerId = DataGenerater::userGenerater([]);
        $kanban = $this->getKanbanModule()->create('UnitTest Kanban', 'A kanban for unit test!', $ownerId);
        $kanbanId = $kanban->id;

        $userId = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid3',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest3',
            'mobile' => '18011112224']
        );
        $this->getKanbanMemberModule()->joinKanban($kanbanId, $userId, KanbanMember::MEMBER_ROLE_USER);
        $members = $this->getKanbanMemberModule()->getMembersWithUserInfo($kanbanId);

        $this->assertCount(2, $members);
        foreach ($members as $m) {
            $this->assertArrayHasKey('kanban_id', $m);   
            $this->assertArrayHasKey('memeber_role', $m);   
            $this->assertArrayHasKey('email', $m);   
            $this->assertArrayHasKey('uuid', $m);   
            $this->assertArrayHasKey('name', $m);   
            $this->assertArrayHasKey('mobile', $m);   
            $this->assertArrayHasKey('reg_type', $m);   
        }
    }

    public function testGetMemberIds()
    {
        $ownerId = DataGenerater::userGenerater([]);

        $kanban = $this->getKanbanModule()->create('UnitTest Kanban', 'A kanban for unit test!', $ownerId);
        $newKanbanId = $kanban->id;
        $userId1 = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid1',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223']);
        $this->getKanbanModule()->joinKanban($newKanbanId, $userId1, Kanban::MEMBER_ROLE_ADMIN);
        $userId2 = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid2',
            'email' => 'unittest3@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest2',
            'mobile' => '18011112224']);
        $this->getKanbanMemberModule()->joinKanban($newKanbanId, $userId2,  Kanban::MEMBER_ROLE_USER);

        $userIds = [$ownerId, $userId1, $userId2];
        $kanbanMemberIds = $this->getKanbanMemberModule()->getMemberIds($newKanbanId);
        foreach ($kanbanMemberIds as $mid) {
            $this->assertTrue(in_array($mid, $userIds));
        }
    }

    public function testGetUserKanbans()
    {
        $ownerId = DataGenerater::userGenerater([]);
        $kanbanId1 = $this->getKanbanModule()->create('like UnitTest Kanban1', 'like kanban for unit test!', $ownerId);
        $kanbanId2 = $this->getKanbanModule()->create('UnitTestlike Kanban2', 'A kanban for unit like test!', $ownerId);
        $kanbanId3 = $this->getKanbanModule()->create('UnitTest Kanban3like', 'A kanban for unit test! like', $ownerId);
        $kanbanId4 = $this->getKanbanModule()->create('UnitTest Kanban4', 'A kanban for unit test!', $ownerId);

        $userId = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid1',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223'
        ]); 
        $this->getKanbanMemberModule()->joinKanban($kanbanId1, $userId, KanbanMember::MEMBER_ROLE_USER);
        $this->getKanbanMemberModule()->joinKanban($kanbanId2, $userId, KanbanMember::MEMBER_ROLE_USER);
        $this->getKanbanMemberModule()->joinKanban($kanbanId3, $userId, KanbanMember::MEMBER_ROLE_ADMIN);
        $this->getKanbanMemberModule()->joinKanban($kanbanId4, $userId, KanbanMember::MEMBER_ROLE_USER);

        $this->getKanbanModule()->close($kanbanId4);

        $kanbans = $this->getKanbanMemberModule()->getUserKanbans($userId);
        $this->assertCount(3, $kanbans);

        $kanbans = $this->getKanbanMemberModule()->getUserKanbans($userId, 'like');
        $this->assertCount(3, $kanbans);
        $id = 0;
        foreach ($kanbans as $k) {
            if (!$id) {
                $id = $k->id;
                continue;
            }
            $this->assertTrue($id > $k->id);
            $id = $k->id;
        }
    }

    public function testSearchUserKanbans()
    {
        $ownerId = DataGenerater::userGenerater([]);
        $kanbanId1 = $this->getKanbanModule()->create('like UnitTest Kanban1', 'like kanban for unit test!', $ownerId);
        $kanbanId2 = $this->getKanbanModule()->create('UnitTestlike Kanban2', 'A kanban for unit like test!', $ownerId);
        $kanbanId3 = $this->getKanbanModule()->create('UnitTest Kanban3like', 'A kanban for unit test! like', $ownerId);
        $kanbanId4 = $this->getKanbanModule()->create('UnitTest Kanban4', 'A kanban for unit test!', $ownerId);

        $userId = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid1',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223'
        ]); 
        $this->getKanbanMemberModule()->joinKanban($kanbanId1, $userId, KanbanMember::MEMBER_ROLE_USER);
        $this->getKanbanMemberModule()->joinKanban($kanbanId2, $userId, KanbanMember::MEMBER_ROLE_USER);
        $this->getKanbanMemberModule()->joinKanban($kanbanId3, $userId, KanbanMember::MEMBER_ROLE_ADMIN);
        $this->getKanbanMemberModule()->joinKanban($kanbanId4, $userId, KanbanMember::MEMBER_ROLE_USER);

        $this->getKanbanModule()->close($kanbanId4);

        $kanbans = $this->getKanbanMemberModule()->searchUserKanbans($userId, 'like', ['*'], 0, 2);
        $this->assertCount(2, $kanbans);
    }

    public function testGetUserManageKanbans()
    {
        $ownerId = DataGenerater::userGenerater([]);
        $kanbanId1 = $this->getKanbanModule()->create('like UnitTest Kanban1', 'like kanban for unit test!', $ownerId);
        $kanbanId2 = $this->getKanbanModule()->create('UnitTestlike Kanban2', 'A kanban for unit like test!', $ownerId);
        $kanbanId3 = $this->getKanbanModule()->create('UnitTest Kanban3like', 'A kanban for unit test! like', $ownerId);
        $kanbanId4 = $this->getKanbanModule()->create('UnitTest Kanban4', 'A kanban for unit test!', $ownerId);

        $userId = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid1',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223'
        ]); 
        $this->getKanbanMemberModule()->joinKanban($kanbanId1, $userId, KanbanMember::MEMBER_ROLE_USER);
        $this->getKanbanMemberModule()->joinKanban($kanbanId2, $userId, KanbanMember::MEMBER_ROLE_USER);
        $this->getKanbanMemberModule()->joinKanban($kanbanId3, $userId, KanbanMember::MEMBER_ROLE_ADMIN);
        $this->getKanbanMemberModule()->joinKanban($kanbanId4, $userId, KanbanMember::MEMBER_ROLE_ADMIN);

        $this->getKanbanModule()->close($kanbanId4);

        $kanbans = $this->getKanbanMemberModule()->getUserManageKanbans($userId);
        $this->assertCount(1, $kanbans);
        
    }

    public function testGetUserClosedKanbans()
    {
        $ownerId = DataGenerater::userGenerater([]);
        $kanbanId1 = $this->getKanbanModule()->create('like UnitTest Kanban1', 'like kanban for unit test!', $ownerId);
        $kanbanId2 = $this->getKanbanModule()->create('UnitTestlike Kanban2', 'A kanban for unit like test!', $ownerId);
        $kanbanId3 = $this->getKanbanModule()->create('UnitTest Kanban3like', 'A kanban for unit test! like', $ownerId);
        $kanbanId4 = $this->getKanbanModule()->create('UnitTest Kanban4', 'A kanban for unit test!', $ownerId);

        $userId = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid1',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223'
        ]); 
        $this->getKanbanMemberModule()->joinKanban($kanbanId1, $userId, KanbanMember::MEMBER_ROLE_USER);
        $this->getKanbanMemberModule()->joinKanban($kanbanId2, $userId, KanbanMember::MEMBER_ROLE_USER);
        $this->getKanbanMemberModule()->joinKanban($kanbanId3, $userId, KanbanMember::MEMBER_ROLE_ADMIN);
        $this->getKanbanMemberModule()->joinKanban($kanbanId4, $userId, KanbanMember::MEMBER_ROLE_ADMIN);

        $this->getKanbanModule()->close($kanbanId4);

        $kanbans = $this->getKanbanMemberModule()->getUserClosedKanbans($userId);
        $this->assertCount(1, $kanbans);
    }

}
