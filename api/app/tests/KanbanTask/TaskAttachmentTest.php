<?php
namespace app\tests\KanbanTask;

use app\common\event\AsyncEvent;
use app\common\exception\ResourceNotFoundException;
use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\common\exception\InvalidParamsException;
use app\module\Attachment;
use app\module\KanbanMember;
use app\module\TaskCheckList;
use app\tests\Base;
use app\tests\Common\DataGenerater;
use support\bootstrap\Container;
use Webman\Http\UploadFile;

class TaskAttachmentTest extends Base
{

    public function testUploadWithoutPermission()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);

        $userId = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        // $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId, KanbanMember::MEMBER_ROLE_USER);

        $file = $this->createMock(UploadFile::class);
        $file->method('getSize')->willReturn('100');
        $file->method('getUploadName')->willReturn("unittest.file");

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('access_denied');
        $this->getAttachmentModule()->taskAttachment($file, $taskId, $userId);
    }

    public function testUploadWithNotFoundTask()
    {
        $taskIdNotExist = 1000000;
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);

        $userId = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId, KanbanMember::MEMBER_ROLE_USER);

        $fileName = 'unittest.file';
        $fileSize = 6 * 1024 * 1024;

        $file = $this->createMock(UploadFile::class);
        $file->method('getSize')->willReturn($fileSize);
        $file->method('getUploadName')->willReturn($fileName);
        $file->method('getUploadExtension')->willReturn("png");
        $file->method('getUploadMineType')->willReturn("image");
        $file->method('getUploadMineType')->willReturn("image");
        $file->method('move')->willReturn(true);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('task.not_found');
        $this->getAttachmentModule()->taskAttachment($file, $taskIdNotExist, $userId);
    }

    public function testSuccessAndSizeLimit()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);

        $userId = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId, KanbanMember::MEMBER_ROLE_USER);

        $fileName = 'unittest.file';
        $fileSize = 6 * 1024 * 1024;

        $file = $this->createMock(UploadFile::class);
        $file->method('getSize')->willReturn($fileSize);
        $file->method('getUploadName')->willReturn($fileName);
        $file->method('getUploadExtension')->willReturn("png");
        $file->method('getUploadMineType')->willReturn("image");
        $file->method('getUploadMineType')->willReturn("image");
        $file->method('move')->willReturn(true);

        $newAttachment = $this->getAttachmentModule()->taskAttachment($file, $taskId, $userId);
        $this->assertNotEmpty($newAttachment);
        $this->assertEquals($newAttachment->copy_from_id, 0);
        $this->assertEquals($newAttachment->task_id, $taskId);
        $this->assertEquals($newAttachment->user_id, $userId);
        $this->assertEquals($newAttachment->created_time, time());
        $this->assertEquals($newAttachment->org_name, $fileName);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('file.size.too_big');
        $file2 = $this->createMock(UploadFile::class);
        $file2->method('getSize')->willReturn($fileSize + 1);
        $file2->method('getUploadName')->willReturn($fileName);
        $file2->method('getUploadExtension')->willReturn("png");
        $file2->method('getUploadMineType')->willReturn("image");
        $file2->method('getUploadMineType')->willReturn("image");
        $file2->method('move')->willReturn(true);
        $this->getAttachmentModule()->taskAttachment($file2, $taskId, $userId);
    }

    public function testMoveFileFailed()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);

        $userId = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId, KanbanMember::MEMBER_ROLE_USER);

        $fileName = 'unittest.file';
        $fileSize = 6 * 1024 * 1024;

        $file = $this->createMock(UploadFile::class);
        $file->method('getSize')->willReturn($fileSize);
        $file->method('getUploadName')->willReturn($fileName);
        $file->method('getUploadExtension')->willReturn("png");
        $file->method('getUploadMineType')->willReturn("image");
        $file->method('getUploadMineType')->willReturn("image");
        $file->method('move')->willReturn(false);

        $newAttachment = $this->getAttachmentModule()->taskAttachment($file, $taskId, $userId);
        $this->assertFalse($newAttachment);
    }

    public function testGetByUri()
    {
        $newAttachment = $this->generateAttachment();
        $attachment = $this->getAttachmentModule()->getByUri($newAttachment->file_uri);
        $this->assertNotEmpty($attachment);

        $notExistUri = 'not_exist_uri';
        $attachment = $this->getAttachmentModule()->getByUri($notExistUri);
        $this->assertEmpty($attachment);
    }

    public function testGetById()
    {
        $newAttachment = $this->generateAttachment();
        $attachment = $this->getAttachmentModule()->getById($newAttachment->id);
        $this->assertNotEmpty($attachment);

        $notExistId = '1919191';
        $attachment = $this->getAttachmentModule()->getById($notExistId);
        $this->assertEmpty($attachment);
    }

    public function testGetTaskAttachments()
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);

        $userId = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId, KanbanMember::MEMBER_ROLE_USER);

        $fileName = 'unittest.file';
        $fileSize = 6 * 1024 * 1024;

        $file = $this->createMock(UploadFile::class);
        $file->method('getSize')->willReturn($fileSize);
        $file->method('getUploadName')->willReturn($fileName);
        $file->method('getUploadExtension')->willReturn("png");
        $file->method('getUploadMineType')->willReturn("image");
        $file->method('getUploadMineType')->willReturn("image");
        $file->method('move')->willReturn(true);

        $count = 10;
        for ($i = 0; $i < $count; $i ++) {
            $newAttachment = $this->getAttachmentModule()->taskAttachment($file, $taskId, $userId);
            $this->assertNotEmpty($newAttachment);
        }
        $attachments = $this->getAttachmentModule()->getTaskAttachments($taskId, $userId);
        $this->assertCount(10, $attachments);

        $preId = 0;
        foreach ($attachments as $a) {
            if (!$preId) {
                $preId = $a->id;
                continue;
            }
            $this->assertTrue($preId > $a->id);
        }

        $userIdNotNoPermission = DataGenerater::userGenerater(['uuid' => 'unittestuuid2', 'email' => 'unittest2@example.com']);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('access_denied');
        $this->getAttachmentModule()->getTaskAttachments($taskId, $userIdNotNoPermission);
    }

    public function testDelete()
    {
        $a = $this->generateAttachment();
        $delete = $this->getAttachmentModule()->delete($a->task_id, $a->id, $a->user_id);
        $this->assertTrue($delete);
    }

    public function testDeleteWithoutPermission()
    {
        $a = $this->generateAttachment();
        $userIdNotNoPermission = DataGenerater::userGenerater(['uuid' => 'unittestuuid2', 'email' => 'unittest2@example.com']);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('access_denied');
        $this->getAttachmentModule()->delete($a->task_id, $a->id, $userIdNotNoPermission);
    }

    public function testDeleteTaskNotFound()
    {
        $a = $this->generateAttachment();
        
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('task.not_found');
        $this->getAttachmentModule()->delete(1000, $a->id, $a->user_id);
    }

    public function testCopyToTask()
    {
        $a = $this->generateAttachment();
        $toTaskId = DataGenerater::createTask(['title' => 'unittest2'], 0, $a->kanban_id, $a->user_id);

        $userId = DataGenerater::userGenerater(['uuid' => 'unittestuuid2']);
        $this->getKanbanMemberModule()->joinKanban($a->kanban_id, $userId, KanbanMember::MEMBER_ROLE_USER);

        $copy = $this->getAttachmentModule()->copyToTask($a->id, $toTaskId, $userId);
        $this->assertNotEmpty($copy);
        $this->assertEquals($copy->copy_from_id, $a->id);
        $this->assertEquals($copy->task_id, $toTaskId);
        $this->assertEquals($copy->user_id, $userId);

        $shouldEqualsFields = [
            'title',
            'file_uri',
            'size',
            'mine_type',
            'org_name',
            'extension',
        ];
        foreach ($shouldEqualsFields as $field) {
            $this->assertEquals($copy->{$field}, $a->{$field});
        }
        // Test copy to the same card
        $copy = $this->getAttachmentModule()->copyToTask($a->id, $a->task_id, $userId);
        $this->assertNotEmpty($copy);
        $this->assertEquals($copy->id, $a->id);
    }

    public function testCopyToTaskWhenTaskNotFound()
    {
        $a = $this->generateAttachment();
        $toTaskId = 1999;

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('task.not_found');
        $this->getAttachmentModule()->copyToTask($a->id, $toTaskId, $a->user_id);
    }

    public function testCopyToTaskWhenFileNotExist()
    {
        $a = $this->generateAttachment();
        $toTaskId = DataGenerater::createTask(['title' => 'unittest2'], 0, $a->kanban_id, $a->user_id);

        $copyFileId = 9999;

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('file.not_found');
        $this->getAttachmentModule()->copyToTask($copyFileId, $toTaskId, $a->user_id);
    }

    public function testCopyToTaskWithoutPermission()
    {
        $a = $this->generateAttachment();
        $toTaskId = DataGenerater::createTask(['title' => 'unittest2'], 0, $a->kanban_id, $a->user_id);

        $userId = 9999;

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('access_denied');
        $this->getAttachmentModule()->copyToTask($a->id, $toTaskId, $userId);
    }

    public function testCopyToTaskCrossKanban()
    {
        $a = $this->generateAttachment();
        $toTaskId = DataGenerater::createTask(['title' => 'unittest2'], 0, 0, $a->user_id);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Copy attahcment can only in the same kanban!');
        $this->getAttachmentModule()->copyToTask($a->id, $toTaskId, $a->user_id);
    }

    public function testSearcheKanbanAttachmentsForCopyWithoutKeyword()
    {
        $a = $this->generateAttachment();
        $attachments = $this->getAttachmentModule()->searchKanbanAttachmentsForCopy($a->user_id, $a->kanban_id);
        $this->assertCount(1, $attachments);

        $file = $this->createMock(UploadFile::class);
        $file->method('getSize')->willReturn(100);
        $file->method('getUploadName')->willReturn('test files');
        $file->method('getUploadExtension')->willReturn("png");
        $file->method('getUploadMineType')->willReturn("image");
        $file->method('getUploadMineType')->willReturn("image");
        $file->method('move')->willReturn(true);

        $this->getAttachmentModule()->taskAttachment($file, $a->task_id, $a->user_id);

        $attachments = $this->getAttachmentModule()->searchKanbanAttachmentsForCopy($a->user_id, $a->kanban_id);
        $this->assertCount(2, $attachments);

        $toTaskId = DataGenerater::createTask(['title' => 'unittest2'], 0, $a->kanban_id, $a->user_id);
        $this->getAttachmentModule()->copyToTask($a->id, $toTaskId, $a->user_id);
        $attachments = $this->getAttachmentModule()->searchKanbanAttachmentsForCopy($a->user_id, $a->kanban_id);
        $this->assertCount(2, $attachments);
    }

    public function testSearcheKanbanAttachmentsForCopyWithKeyword()
    {
        $fileName = '00aaabbb.txt';
        $fileName2 = '我的文件';
        $fileName3 = '我的0文件';
        $taskTitle = '11ccc0ddd';

        $taskId1 = DataGenerater::createTask(['title' => $taskTitle]);
        $task = $this->getKanbanTaskModule()->getTask($taskId1);
        $userId = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId, KanbanMember::MEMBER_ROLE_USER);

        $file1 = $this->createMock(UploadFile::class);
        $file1->method('getSize')->willReturn(100);
        $file1->method('getUploadName')->willReturn($fileName);
        $file1->method('getUploadExtension')->willReturn("png");
        $file1->method('getUploadMineType')->willReturn("image");
        $file1->method('getUploadMineType')->willReturn("image");
        $file1->method('move')->willReturn(true);

        $file2 = $this->createMock(UploadFile::class);
        $file2->method('getSize')->willReturn(100);
        $file2->method('getUploadName')->willReturn($fileName2);
        $file2->method('getUploadExtension')->willReturn("png");
        $file2->method('getUploadMineType')->willReturn("image");
        $file2->method('getUploadMineType')->willReturn("image");
        $file2->method('move')->willReturn(true);

        $file3 = $this->createMock(UploadFile::class);
        $file3->method('getSize')->willReturn(100);
        $file3->method('getUploadName')->willReturn($fileName3);
        $file3->method('getUploadExtension')->willReturn("png");
        $file3->method('getUploadMineType')->willReturn("image");
        $file3->method('getUploadMineType')->willReturn("image");
        $file3->method('move')->willReturn(true);

        $attachment1 = $this->getAttachmentModule()->taskAttachment($file1, $taskId1, $userId);
        $attachments = $this->getAttachmentModule()->searchKanbanAttachmentsForCopy($userId, $task->kanban_id, 0);
        $this->assertCount(1, $attachments);
        $this->getAttachmentModule()->taskAttachment($file2, $taskId1, $userId);

        $attachments = $this->getAttachmentModule()->searchKanbanAttachmentsForCopy($userId, $task->kanban_id, 0);
        $this->assertCount(2, $attachments); // 卡片名称 11ccc0ddd 包含0，搜索关键词匹配卡片名称或者文件名称

        // 测试字符串0也能被搜索
        $attachments = $this->getAttachmentModule()->searchKanbanAttachmentsForCopy($userId, $task->kanban_id, '0');
        $this->assertCount(2, $attachments);

        $this->getAttachmentModule()->taskAttachment($file3, $taskId1, $userId);
        $attachments = $this->getAttachmentModule()->searchKanbanAttachmentsForCopy($userId, $task->kanban_id, '0');
        $this->assertCount(3, $attachments);


        $attachments = $this->getAttachmentModule()->searchKanbanAttachmentsForCopy($userId, $task->kanban_id, '文件');
        $this->assertCount(2, $attachments);

        $attachments = $this->getAttachmentModule()->searchKanbanAttachmentsForCopy($userId, $task->kanban_id, 'ccc0dd');
        $this->assertCount(3, $attachments);

        $attachments = $this->getAttachmentModule()->searchKanbanAttachmentsForCopy($userId, $task->kanban_id, 'bb.txt');
        $this->assertCount(1, $attachments);

        $toTaskId1 = DataGenerater::createTask(['title' => 'unittest2'], 0, $task->kanban_id, $userId);
        $this->getAttachmentModule()->copyToTask($attachment1->id, $toTaskId1, $userId);

        $attachments = $this->getAttachmentModule()->searchKanbanAttachmentsForCopy($userId, $task->kanban_id, '0');
        $this->assertCount(3, $attachments);

        $toTaskId2 = DataGenerater::createTask(['title' => 'unit000test2'], 0, $task->kanban_id, $userId);
        $attachments = $this->getAttachmentModule()->searchKanbanAttachmentsForCopy($userId, $task->kanban_id, '0');
        $this->assertCount(3, $attachments);

        $attachments = $this->getAttachmentModule()->searchKanbanAttachmentsForCopy($userId, $task->kanban_id, '0', [$taskId1]);
        $this->assertCount(0, $attachments);
    }

    public function testSearcheKanbanAttachmentsForCopyWithoutPermission()
    {
        $a = $this->generateAttachment();
        $userId = DataGenerater::userGenerater(['uuid' => 'unittestuuid2']);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('access_denied');
        $this->getAttachmentModule()->searchKanbanAttachmentsForCopy($userId, $a->kanban_id, '0');
    }

    private function generateAttachment($fileName = '')
    {
        $taskId = DataGenerater::createTask();
        $task = $this->getKanbanTaskModule()->getTask($taskId);

        $userId = DataGenerater::userGenerater(['uuid' => 'unittestuuid1']);
        $this->getKanbanMemberModule()->joinKanban($task->kanban_id, $userId, KanbanMember::MEMBER_ROLE_USER);

        if (!$fileName) {
            $fileName = 'unittest.file';
        }
        
        $fileSize = 6 * 1024 * 1024;

        $file = $this->createMock(UploadFile::class);
        $file->method('getSize')->willReturn($fileSize);
        $file->method('getUploadName')->willReturn($fileName);
        $file->method('getUploadExtension')->willReturn("png");
        $file->method('getUploadMineType')->willReturn("image");
        $file->method('getUploadMineType')->willReturn("image");
        $file->method('move')->willReturn(true);

        $newAttachment = $this->getAttachmentModule()->taskAttachment($file, $taskId, $userId);

        return $newAttachment;
    }

}
