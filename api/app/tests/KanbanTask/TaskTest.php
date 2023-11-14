<?php
namespace app\tests\KanbanTask;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\tests\Base;
use support\Db;
use app\tests\Common\DataGenerater;

class TaskTest extends Base
{

    public function testGetTaskLogActions()
    {
        $this->assertCount(23, $this->getKanbanTaskModule()->getTaskLogActions());
    }

    public function testGetDueNotifyTimes()
    {
        $this->assertCount(10, $this->getKanbanTaskModule()->getDueNotifyTimes());
        $this->assertCount(10, $this->getKanbanTaskModule()->getDueNotifyTimeTxt());
    }

    public function testGetTask()
    {
        $userId = DataGenerater::userGenerater([]);
        $kanbanId = $this->getKanbanModule()->create('UnitTest', 'Unit Test Board', $userId);
        $kanban = $this->getKanbanModule()->get($kanbanId);
        $kanbanList = $this->getKanbanModule()->getKanbanList($kanbanId);

        $listIds = [];
        foreach ($kanbanList as $l) {
            $listIds[] = $l->id;
        }
        $task = ['title' => 'unittest', 'desc' => '', 'kanbanId' => $kanban->id];
        $listId = array_pop($listIds);
        $taskId = $this->getKanbanTaskModule()->createTask($task, $userId, $listId);

        $task = $this->getKanbanTaskModule()->getTask($taskId, ['id']); // 测试只输入ID时，也会返回done等状态
        $this->assertArrayHasKey('end_date', $task);
        $this->assertEmpty($task->end_date);
        $this->assertArrayHasKey('done', $task);
        $this->assertFalse($task->done);
        $this->assertArrayHasKey('is_due_soon', $task);
        $this->assertFalse($task->is_due_soon);
        $this->assertArrayHasKey('overfall', $task);
        $this->assertFalse($task->overfall);

        $dueSoonDate = date("Y-m-d H:i:s", time() + 86398); // 一天内，快过期
        $setDueDate = $this->getKanbanTaskModule()->setDate($taskId, ['end_date' => $dueSoonDate], 0, $userId);
        $this->assertTrue($setDueDate);

        $task = $this->getKanbanTaskModule()->getTask($taskId); 
        $this->assertEquals($task->end_date, date('Y-m-d H:i', strtotime($dueSoonDate)));
        $this->assertTrue($task->is_due_soon);
        $this->assertFalse($task->overfall);


        $overfallDate = date("Y-m-d H:i:s", time() + 86410);
        $setDueDate = $this->getKanbanTaskModule()->setDate($taskId, ['end_date' => $overfallDate], 0, $userId);
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertTrue($setDueDate);
        $this->assertFalse($task->is_due_soon);
        $this->assertFalse($task->overfall);

        DB::table('kanban_task')->where('id', $taskId)->update(['end_time' => time() - 10]);

        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertFalse($task->is_due_soon);
        $this->assertTrue($task->overfall);
    }

    public function testGetKanbanTasks()
    {
        $userId = DataGenerater::userGenerater([]);
        $kanbanId = $this->getKanbanModule()->create('UnitTest', 'Unit Test Board', $userId);
        $kanban = $this->getKanbanModule()->get($kanbanId);
        $kanbanList = $this->getKanbanModule()->getKanbanList($kanbanId);

        $listIds = [];
        foreach ($kanbanList as $l) {
            $listIds[] = $l->id;
        }
        $task = ['title' => 'unittest', 'desc' => '', 'kanbanId' => $kanban->id];
        $listId = array_pop($listIds);
        $taskId1 = $this->getKanbanTaskModule()->createTask($task, $userId, $listId);
        $taskId2 = $this->getKanbanTaskModule()->createTask($task, $userId, $listId);

        $tasks = $this->getKanbanTaskModule()->getKanbanTasks($kanbanId);
        $this->assertCount(2, $tasks);
        $this->getKanbanTaskModule()->archive($taskId1, $userId);
        $tasks = $this->getKanbanTaskModule()->getKanbanTasks($kanbanId);
        $this->assertCount(1, $tasks);

        $tasks = $this->getKanbanTaskModule()->getKanbanTasks($kanbanId, ['*'], true);
        $this->assertCount(2, $tasks);
    }

    public function testMarkDoneWithError()
    {
        $userId = DataGenerater::userGenerater([]);
        $kanbanId = $this->getKanbanModule()->create('UnitTest', 'Unit Test Board', $userId);
        $kanban = $this->getKanbanModule()->get($kanbanId);
        $kanbanList = $this->getKanbanModule()->getKanbanList($kanbanId);

        $listIds = [];
        foreach ($kanbanList as $l) {
            $listIds[] = $l->id;
        }
        $task = ['title' => 'unittest', 'desc' => '', 'kanbanId' => $kanban->id];
        $listId = array_pop($listIds);
        $taskId = $this->getKanbanTaskModule()->createTask($task, $userId, $listId);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('access_denied');
        $this->getKanbanTaskModule()->markDone($taskId, 100000);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('task.not_found');
        $this->getKanbanTaskModule()->markDone(100000, $userId);
    }

    public function testMarkDone()
    {
        $userId = DataGenerater::userGenerater([]);
        $kanbanId = $this->getKanbanModule()->create('UnitTest', 'Unit Test Board', $userId);
        $kanban = $this->getKanbanModule()->get($kanbanId);
        $kanbanList = $this->getKanbanModule()->getKanbanList($kanbanId);

        $listIds = [];
        foreach ($kanbanList as $l) {
            $listIds[] = $l->id;
        }
        $task = ['title' => 'unittest', 'desc' => '', 'kanbanId' => $kanban->id];
        $listId = array_pop($listIds);
        $taskId = $this->getKanbanTaskModule()->createTask($task, $userId, $listId);
        $done = $this->getKanbanTaskModule()->markDone($taskId, $userId);
        $this->assertTrue($done == 1);
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertTrue($task->finished_time > 0);
        $this->assertEquals(1, $task->is_finished);
        $remark = $this->getKanbanTaskModule()->markDone($taskId, $userId);
        $this->assertTrue($remark);

        $userIds2 = DataGenerater::userGenerater(['uuid' => 'unittestuuid1', 'email' => 'unittest2@example.com']);
        $this->getKanbanModule()->joinKanban($kanbanId, $userIds2, 2); // 加入看板，普通成员
        $undone = $this->getKanbanTaskModule()->markUndone($taskId, $userIds2);
        $this->assertEquals(1, $undone);
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertEquals(0, $task->finished_time);
        $this->assertEquals(0, $task->is_finished);
    }

    public function testArchiveAndUnArchive()
    {
        $userId = DataGenerater::userGenerater([]);
        $kanbanId = $this->getKanbanModule()->create('UnitTest', 'Unit Test Board', $userId);
        $kanban = $this->getKanbanModule()->get($kanbanId);
        $kanbanList = $this->getKanbanModule()->getKanbanList($kanbanId);

        $listIds = [];
        foreach ($kanbanList as $l) {
            $listIds[] = $l->id;
        }
        $task = ['title' => 'unittest', 'desc' => '', 'kanbanId' => $kanban->id];
        $listId = array_pop($listIds);
        $taskId = $this->getKanbanTaskModule()->createTask($task, $userId, $listId);

        $list = $this->getKanbanModule()->getList($listId);
        $this->assertEquals(1, $list->task_count);
        
        $archived = $this->getKanbanTaskModule()->archive($taskId, $userId);
        $list = $this->getKanbanModule()->getList($listId);
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertEquals(1, $task->archived);
        $this->assertTrue($archived);
        $this->assertEquals(0, $list->task_count);

        $unarchived = $this->getKanbanTaskModule()->unarchive($taskId, $userId);
        $this->assertTrue($unarchived);
        $task = $this->getKanbanTaskModule()->getTask($taskId);
        $this->assertEquals(0, $task->archived);

        $list = $this->getKanbanModule()->getList($listId);
        $this->assertEquals(1, $list->task_count);
    }

    public function testReArvhive()
    {
        $userId = DataGenerater::userGenerater([]);
        $kanbanId = $this->getKanbanModule()->create('UnitTest', 'Unit Test Board', $userId);
        $kanban = $this->getKanbanModule()->get($kanbanId);
        $kanbanList = $this->getKanbanModule()->getKanbanList($kanbanId);

        $listIds = [];
        foreach ($kanbanList as $l) {
            $listIds[] = $l->id;
        }
        $task = ['title' => 'unittest', 'desc' => '', 'kanbanId' => $kanban->id];
        $listId = array_pop($listIds);
        $taskId1 = $this->getKanbanTaskModule()->createTask($task, $userId, $listId);
        $archive = $this->getKanbanTaskModule()->archive($taskId1, $userId);
        $list = $this->getKanbanModule()->getList($listId);
        $this->assertEquals(0, $list->task_count);
        $reArchive = $this->getKanbanTaskModule()->archive($taskId1, $userId);
        $list = $this->getKanbanModule()->getList($listId);
        $this->assertEquals(0, $list->task_count);
        $this->assertTrue($archive);
        $this->assertTrue($reArchive);
    }

    public function testUnArchivedWithWipLimit()
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('task.unarchive.error.wip_limit');
        $userId = DataGenerater::userGenerater([]);
        $kanbanId = $this->getKanbanModule()->create('UnitTest', 'Unit Test Board', $userId);
        $kanban = $this->getKanbanModule()->get($kanbanId);
        $kanbanList = $this->getKanbanModule()->getKanbanList($kanbanId);

        $listIds = [];
        foreach ($kanbanList as $l) {
            $listIds[] = $l->id;
        }
        $task = ['title' => 'unittest', 'desc' => '', 'kanbanId' => $kanban->id];
        $listId = array_pop($listIds);
        $taskId1 = $this->getKanbanTaskModule()->createTask($task, $userId, $listId);
        $taskId2 = $this->getKanbanTaskModule()->createTask($task, $userId, $listId);
        $this->getKanbanModule()->setWip($listId, 2, $userId);
        $this->getKanbanTaskModule()->archive($taskId1, $userId);
        $this->getKanbanModule()->setWip($listId, 1, $userId);

        $this->getKanbanTaskModule()->unarchive($taskId1, $userId);
    }

}
