<?php
namespace app\tests\KanbanTask;

use app\common\event\AsyncEvent;
use app\common\exception\ResourceNotFoundException;
use app\common\exception\AccessDeniedException;
use app\common\exception\InvalidParamsException;
use app\module\KanbanMember;
use app\module\TaskCheckList;
use app\tests\Base;
use app\tests\Common\DataGenerater;
use support\bootstrap\Container;

class TaskCheckListTest extends Base
{
    protected function setUp(): void
    {
        $evStub = $this->getMockBuilder(AsyncEvent::class)
                     ->disableOriginalConstructor()
                     ->disableOriginalClone()
                     ->disableArgumentCloning()
                     ->disallowMockingUnknownTypes()
                     ->getMock();
        $evStub->method('emit')
             ->willReturn(true);

        Container::set('async.event', $evStub);
    }

    public function testGetByTaskId()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $userId2 = DataGenerater::userGenerater(['uuid' => 'unittestuuid2']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_USER);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId2, KanbanMember::MEMBER_ROLE_USER);

        $dueTime = strtotime("+8 day");
        $memberIds = [$userId1, $userId2];
        $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1, $dueTime, $memberIds);
        $this->getTaskCheckListModule()->add($taskId, "Check List 2", $userId1, $dueTime, $memberIds);

        $checklist = $this->getTaskCheckListModule()->getByTaskId($taskId, $userId1);
        $this->assertCount(2, $checklist);
        foreach ($checklist as $c) {
            $this->assertCount(2, $c->members);
        }
    }

    public function testGetByTaskIdWithTaskNotExist()
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->getTaskCheckListModule()->getByTaskId(10000, 1);
    }

    public function testGetByTaskIdWithNoPermission()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(AccessDeniedException::DEFAULT_MSG);
        $this->getTaskCheckListModule()->getByTaskId($taskId, $userId1);
    }

    public function testIsMemberInCheckList()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $userId2 = DataGenerater::userGenerater(['uuid' => 'unittestuuid2']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_USER);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId2, KanbanMember::MEMBER_ROLE_USER);

        $dueTime = strtotime("+8 day");
        $checkListId = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1, $dueTime);
        $this->assertFalse($this->getTaskCheckListModule()->isMemberInCheckList($checkListId, $userId1));
        $this->getTaskCheckListModule()->addMember($checkListId, $userId1, $userId1);
        $this->assertTrue($this->getTaskCheckListModule()->isMemberInCheckList($checkListId, $userId1));
    }

    public function testAddCheckList()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $userId2 = DataGenerater::userGenerater(['uuid' => 'unittestuuid2']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_USER);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId2, KanbanMember::MEMBER_ROLE_USER);

        $dueTime = strtotime("+8 day");
        $memberIds = [$userId1, $userId2];
        $checkListId = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1, $dueTime, $memberIds);
        $this->assertTrue($checkListId > 0);

        $checkList = $this->getTaskCheckListModule()->get($checkListId);
        $this->assertEquals($checkList->id, $checkListId);
        $this->assertCount(2, $checkList->members);
        $this->assertEquals($dueTime, $checkList->due_time);
    }

    public function testAddCheckListWithTaskNotFound()
    {
        $taskId = 0;
        $userId = 0;
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('kanban.not_found');
        $checkListId = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId);
    }

    public function testAddCheckListWithOperatorNoPermission()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $userId2 = DataGenerater::userGenerater(['uuid' => 'unittestuuid2']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_USER);
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('access_denied');
        $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId2, time(), [$userId1]);
    }

    public function testAddCheckListWithMemberNotInKanban()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $userId2 = DataGenerater::userGenerater(['uuid' => 'unittestuuid2']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_USER);

        $added = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1, time(), [$userId1]);
        $this->assertTrue($added > 0);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('access_denied');
        $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1, time(), [$userId1, $userId2]);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('access_denied');
        $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1, time(), [$userId2]);
    }

    public function testAddWithEmptyMember()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId, KanbanMember::MEMBER_ROLE_USER);

        $dueTime = strtotime("+8 day");
        $checkListId = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId, $dueTime);
        $this->assertTrue($checkListId > 0);

        $checkList = $this->getTaskCheckListModule()->get($checkListId);
        $this->assertEquals($checkList->id, $checkListId);
        $this->assertCount(0, $checkList->members);
        $this->assertEquals($dueTime, $checkList->due_time);
    }

    public function testAddWithEmptyDueTime()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $userId2 = DataGenerater::userGenerater(['uuid' => 'unittestuuid2']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_USER);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId2, KanbanMember::MEMBER_ROLE_USER);

        $memberIds = [$userId1, $userId2];
        $checkListId = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1, 0, $memberIds);
        $this->assertTrue($checkListId > 0);

        $checkList = $this->getTaskCheckListModule()->get($checkListId);
        $this->assertEquals($checkList->id, $checkListId);
        $this->assertCount(2, $checkList->members);
        $this->assertEquals(0, $checkList->due_time);
    }

    public function testAddWithEmptyDueTimeAndMember()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_USER);

        $checkListId = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1);
        $this->assertTrue($checkListId > 0);

        $checkList = $this->getTaskCheckListModule()->get($checkListId);
        $this->assertEquals($checkList->id, $checkListId);
        $this->assertCount(0, $checkList->members);
        $this->assertEquals(0, $checkList->due_time);
    }

    public function testAddMember()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $userId2 = DataGenerater::userGenerater(['uuid' => 'unittestuuid2']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_USER);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId2, KanbanMember::MEMBER_ROLE_USER);

        $dueTime = strtotime("+8 day");
        $checkListId = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1, $dueTime);
        $this->assertTrue($checkListId > 0);

        $memberAdded = $this->getTaskCheckListModule()->addMember($checkListId, $userId2, $userId1);
        $this->assertTrue($memberAdded);
        $memberAdded = $this->getTaskCheckListModule()->addMember($checkListId, $userId1, $userId2);
        $this->assertTrue($memberAdded);

        $checkList = $this->getTaskCheckListModule()->get($checkListId);
        $this->assertEquals($checkList->id, $checkListId);
        $this->assertCount(2, $checkList->members);
        $this->assertEquals($dueTime, $checkList->due_time);

        // 测试重复添加
        $memberAdded = $this->getTaskCheckListModule()->addMember($checkListId, $userId2, $userId1);
        $this->assertTrue($memberAdded);
        $memberAdded = $this->getTaskCheckListModule()->addMember($checkListId, $userId1, $userId2);
        $this->assertTrue($memberAdded);

        $checkList = $this->getTaskCheckListModule()->get($checkListId);
        $this->assertCount(2, $checkList->members);
    }

    public function testAddMemberWithCreatorNoPermission()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $creator = DataGenerater::userGenerater(['uuid' => 'unittestuuid2']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_USER);

        $checkListId = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1);
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(AccessDeniedException::DEFAULT_MSG);
        $this->getTaskCheckListModule()->addMember($checkListId, $userId1, $creator);
    }


    public function testAddMemberWithMemberNotInKanban()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $creator = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $memberId = DataGenerater::userGenerater(['uuid' => 'unittestuuid2']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $creator, KanbanMember::MEMBER_ROLE_USER);

        $checkListId = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $creator);
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(AccessDeniedException::DEFAULT_MSG);
        $this->getTaskCheckListModule()->addMember($checkListId, $memberId, $creator);
    }

    public function testAddMemberWithCheckListNotFound()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $creator = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $memberId = DataGenerater::userGenerater(['uuid' => 'unittestuuid2']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $creator, KanbanMember::MEMBER_ROLE_USER);

        $checkListId = 11111;
        $this->expectException(ResourceNotFoundException::class);
        $this->getTaskCheckListModule()->addMember($checkListId, $memberId, $creator);
    }

    public function testRemoveMember()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $userId2 = DataGenerater::userGenerater(['uuid' => 'unittestuuid2']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_USER);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId2, KanbanMember::MEMBER_ROLE_USER);

        $dueTime = strtotime("+8 day");
        $memberIds = [$userId1, $userId2];
        $checkListId = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1, $dueTime, $memberIds);
        $this->assertTrue($checkListId > 0);

        $checkList = $this->getTaskCheckListModule()->get($checkListId);
        $this->assertEquals($checkList->id, $checkListId);
        $this->assertCount(2, $checkList->members);
        $this->assertEquals($dueTime, $checkList->due_time);

        $remove = $this->getTaskCheckListModule()->removeMember($checkListId, $userId2, $userId1);
        $this->assertEquals(1, $remove);
        $checkList = $this->getTaskCheckListModule()->get($checkListId);
        $this->assertCount(1, $checkList->members);
        $member1 = $checkList->members[0];
        $this->assertEquals($member1->id, $userId1);
    }

    public function testChangeTitle()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_ADMIN);
        $dueTime = strtotime("+8 day");
        $checkListId = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1, $dueTime);
        $newTitle = "Check List New Title";
        $change = $this->getTaskCheckListModule()->changeTitle($checkListId, $userId1, $newTitle);
        $this->assertTrue((bool) $change);
        $checkList = $this->getTaskCheckListModule()->get($checkListId);
        $this->assertEquals($newTitle, $checkList->title);
    }

    public function testChangeTitleWithOperatorNoPermission()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $userId2 = DataGenerater::userGenerater(['uuid' => 'unittestuuid2']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_ADMIN);
        $checkListId = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1);
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(AccessDeniedException::DEFAULT_MSG);
        $this->getTaskCheckListModule()->changeTitle($checkListId, $userId2, 'aaa');
    }

    public function testChangeTitleWithTitleTooLong()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_ADMIN);
        $checkListId = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1);
        $newTitle = str_repeat('a', TaskCheckList::MAX_TITLE_LEN);
        $change = $this->getTaskCheckListModule()->changeTitle($checkListId, $userId1, $newTitle);
        $this->assertTrue((bool) $change);
        $newTitle = str_repeat('a', TaskCheckList::MAX_TITLE_LEN+1);
        $this->expectException(InvalidParamsException::class);
        $change = $this->getTaskCheckListModule()->changeTitle($checkListId, $userId1, $newTitle);
    }

    public function testChangeTitleWithEmptyTitle()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_ADMIN);
        $checkListId = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1);
        $newTitle = '';
        $this->expectException(InvalidParamsException::class);
        $change = $this->getTaskCheckListModule()->changeTitle($checkListId, $userId1, $newTitle);
    }

    public function testChangeTitleWithNotExistCheckList()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_ADMIN);
        $checkListId = 11111111111111111111;
        $newTitle = 'aaaaa';
        $this->expectException(ResourceNotFoundException::class);
        $change = $this->getTaskCheckListModule()->changeTitle($checkListId, $userId1, $newTitle);
    }

    public function testMarkDone()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_USER);

        $dueTime = strtotime("+8 day");
        $memberIds = [$userId1];
        $checkListId1 = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1, $dueTime, $memberIds);
        $checkListId2 = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1, $dueTime, $memberIds);

        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertEquals(2, $task->check_list_num);
        $this->assertEquals(0, $task->check_list_finished_num);
        $markDone = $this->getTaskCheckListModule()->done($checkListId1, $userId1);
        $this->assertTrue($markDone);
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertEquals(2, $task->check_list_num);
        $this->assertEquals(1, $task->check_list_finished_num);

        // 重复markdone
        $markDone = $this->getTaskCheckListModule()->done($checkListId1, $userId1);
        $this->assertTrue($markDone);
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertEquals(2, $task->check_list_num);
        $this->assertEquals(1, $task->check_list_finished_num);

        $undone = $this->getTaskCheckListModule()->undone($checkListId1, $userId1);
        $this->assertTrue($undone);
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertEquals(2, $task->check_list_num);
        $this->assertEquals(0, $task->check_list_finished_num);
    }

    public function testDoneWithChecklistNotExist()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_USER);
        $checkListId1 = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1);
        $this->expectException(ResourceNotFoundException::class);
        $markDone = $this->getTaskCheckListModule()->done(111, $userId1);
    }

    public function testDoneWithNoPermission()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $userId2 = DataGenerater::userGenerater(['uuid' => 'unittestuuid2']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_USER);
        // $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId2, KanbanMember::MEMBER_ROLE_USER);
        $checkListId1 = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1);
        $this->expectException(AccessDeniedException::class);
        $markDone = $this->getTaskCheckListModule()->done($checkListId1, $userId2);
    }

    public function testUnDoneWithChecklistNotExist()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_USER);
        $checkListId1 = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1);
        $this->expectException(ResourceNotFoundException::class);
        $markDone = $this->getTaskCheckListModule()->undone(111, $userId1);
    }

    public function testUnDoneWithNoPermission()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $userId2 = DataGenerater::userGenerater(['uuid' => 'unittestuuid2']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_USER);
        // $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId2, KanbanMember::MEMBER_ROLE_USER);
        $checkListId1 = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1);
        $this->expectException(AccessDeniedException::class);
        $markDone = $this->getTaskCheckListModule()->undone($checkListId1, $userId2);
    }

    public function testDelete()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_USER);

        $checkListId1 = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1);
        $checkListId2 = $this->getTaskCheckListModule()->add($taskId, "Check List 2", $userId1);
        $this->assertTrue($checkListId1 > 0);
        $this->assertCount(2, $this->getTaskCheckListModule()->getByTaskId($taskId, $userId1));
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertEquals(2, $task->check_list_num);
        $del = $this->getTaskCheckListModule()->delete($checkListId1, $userId1);
        $this->assertTrue((bool) $del);
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertEquals(1, $task->check_list_num);
        
        $this->expectException(ResourceNotFoundException::class);
        $del = $this->getTaskCheckListModule()->delete($checkListId1, $userId1);
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertEquals(1, $task->check_list_num);
    }

    public function testDeleteWithChecklistNotFound()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_USER);

        $checkListId1 = 111;
        $this->expectException(ResourceNotFoundException::class);
        $this->getTaskCheckListModule()->delete($checkListId1, $userId1);
    }

    public function testDeleteWithChecklistNoPermission()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $userId2 = DataGenerater::userGenerater(['uuid' => 'unittestuuid2']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_USER);

        $checkListId1 = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1);
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(AccessDeniedException::DEFAULT_MSG);
        $this->getTaskCheckListModule()->delete($checkListId1, $userId2);
    }

    public function testStats()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_USER);

        $dueTime = strtotime("+8 day");
        $memberIds = [$userId1];
        $checkListId1 = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1, $dueTime, $memberIds);
        $checkListId2 = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1, $dueTime, $memberIds);

        $stat = $this->getTaskCheckListModule()->statCheckListNum($taskId);
        $this->assertEquals(2, $stat['total']);
        $this->assertEquals(0, $stat['finished']);

        $this->getTaskCheckListModule()->done($checkListId1, $userId1);
        $stat = $this->getTaskCheckListModule()->statCheckListNum($taskId);
        $this->assertEquals(2, $stat['total']);
        $this->assertEquals(1, $stat['finished']);

        $this->getTaskCheckListModule()->undone($checkListId1, $userId1);
        $stat = $this->getTaskCheckListModule()->statCheckListNum($taskId);
        $this->assertEquals(2, $stat['total']);
        $this->assertEquals(0, $stat['finished']);
    }

    public function testSetDuetime()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertNotEmpty($task->toArray());

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_USER);

        $checkListId1 = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1);
        $checklist = $this->getTaskCheckListModule()->get($checkListId1);
        $this->assertEquals(0, $checklist->due_time);

        $time = time();
        $setDuetime = $this->getTaskCheckListModule()->setDuetime($checkListId1, $time);
        $this->assertTrue((bool) $setDuetime);
        $checklist = $this->getTaskCheckListModule()->get($checkListId1);
        $this->assertEquals($time, $checklist->due_time);

        $time = -1;
        $setDuetime = $this->getTaskCheckListModule()->setDuetime($checkListId1, $time);
        $this->assertTrue((bool) $setDuetime);
        $checklist = $this->getTaskCheckListModule()->get($checkListId1);
        $this->assertEquals(0, $checklist->due_time);
    }

    public function testGetCheckListMembers()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);

        $userId1 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $userId2 = DataGenerater::userGenerater(['uuid' => 'unittestuuid2']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId1, KanbanMember::MEMBER_ROLE_USER);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId2, KanbanMember::MEMBER_ROLE_USER);

        $dueTime = strtotime("+8 day");
        $memberIds = [$userId1, $userId2];
        $checkListId = $this->getTaskCheckListModule()->add($taskId, "Check List 1", $userId1, $dueTime, $memberIds);

        $members = $this->getTaskCheckListModule()->getCheckListMembers($checkListId);

        $this->assertCount(2, $members);
        $rm = $this->getTaskCheckListModule()->removeMember($checkListId, $userId1, $userId2);
        $this->assertTrue(!!$rm);

        $members = $this->getTaskCheckListModule()->getCheckListMembers($checkListId);
        $this->assertCount(1, $members);
    }

}
