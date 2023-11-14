<?php
namespace app\module;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\common\exception\InvalidParamsException;
use app\common\exception\ResourceNotFoundException;
use app\common\oss\OssFactory;
use app\common\toolkit\ArrayHelper;
use app\model\KanbanTaskAttachment as AttachmentModel;
use app\model\KanbanTaskLog;
use app\model\KanbanTask as KanbanTaskModel;
use Webman\Http\UploadFile;
use Ramsey\Uuid\Uuid;
use app\module\KanbanTask;
use support\Db;

class Attachment extends BaseModule
{
    const STATUS_INIT = 'init';
    const STATUS_UPLOADED = 'uploaded';
    const STORAGE_LOCAL = 'local';
    const STORAGE_OSS = 'oss';
    const DEFAULT_MAX_SIZE = 6291456; // 6M
    const PRO_USER_MAX_SIZE = 20971520; // 20M

    public function taskAttachment(UploadFile $file, $taskId, $userId)
    {
        if (!$this->getKanbanModule()->isKanbanMemberByTaskId($userId, $taskId)) {
            throw new AccessDeniedException();
        }
        $size = $file->getSize();
        if ($size > self::DEFAULT_MAX_SIZE) {
            throw new BusinessException('file.size.too_big');
        }
        $name = $file->getUploadName();
        $extension = $file->getUploadExtension();
        $mineType = $file->getUploadMineType();

        $task = $this->getKanbanTaskModule()->getTask($taskId, ['kanban_id']);
        if (!$task) {
            throw new ResourceNotFoundException('task.not_found');
        }
        $uuid = Uuid::uuid4()->toString();
        $fileUri = '/kanban/' . $uuid . '.' . $extension;
        $fullPath = \data_path() . $fileUri;

        $attachment = new AttachmentModel();
        $attachment->uuid = $uuid;
        $attachment->kanban_id = $task->kanban_id;
        $attachment->task_id = $taskId;
        $attachment->file_uri = $fileUri;
        $attachment->size = $size;
        $attachment->mine_type = $mineType;
        $attachment->org_name = $name;
        $attachment->extension = $extension;
        $attachment->user_id = $userId;
        $attachment->created_time = time();

        $log = new KanbanTaskLog();
        $log->kanban_id = $task->kanban_id;
        $log->task_id = $taskId;
        $log->user_id = $userId;
        $log->change = json_encode($attachment);
        $log->action = KanbanTask::TASK_LOG_ACTION_ADD_ATTACHMENT;
        $log->created_time = time();


        if ($file->move($fullPath) && $attachment->save()) {
            $this->getKanbanTaskModule()->incrAttachmentNum($taskId);
            $log->save();
            return $attachment;
        }
        return false;
    }

    public function ossInit($taskId, $userId, $size, $mineType, $name, $ext)
    {
        if ($size > self::DEFAULT_MAX_SIZE) {
            throw new BusinessException('文件大小不超过6MB!');
        }
        $task = $this->getKanbanTaskModule()->getTask($taskId, ['kanban_id', 'uuid']);
        if (!$task) {
            throw new ResourceNotFoundException('task.not_found');
        }
        if (!$this->getKanbanModule()->isMember($task->kanban_id, $userId)) {
            throw new AccessDeniedException();
        }
        $kanban = $this->getKanbanModule()->get($task->kanban_id, ['uuid']);
        $bucket = config('oss.aliyun_oss_bucket');
        if (!$bucket) {
            throw new BusinessException('Miss bucket!');
        }
        $uuid = Uuid::uuid4()->toString();

        try {
            $client = OssFactory::aliyun();
            $keys = $client->getWriteKeys(3600);
        } catch (\Exception $e) {
            throw new BusinessException('Get oss code error!');
        }

        $keys['uuid'] = $uuid;
        $keys['partSize'] = 1024 * 1024; // 分片上传大小
        $keys['bucket'] = $bucket;
        $keys['endpoint'] = config('oss.aliyun_oss_endpoint');
        $keys['sizeLimit'] = self::DEFAULT_MAX_SIZE;
        $path = sprintf('%s/%s/%s', $kanban->uuid, $task->uuid, $uuid);
        $keys['filePath'] = $ext ? $path . '.' .$ext : $path;

        $attachment = new AttachmentModel();
        $attachment->uuid = $uuid;
        $attachment->kanban_id = $task->kanban_id;
        $attachment->task_id = $taskId;
        $attachment->file_uri = $keys['filePath'];
        $attachment->size = $size;
        $attachment->status = self::STATUS_INIT;
        $attachment->storage = self::STORAGE_OSS;
        $attachment->mine_type = $mineType;
        $attachment->org_name = $name;
        $attachment->extension = $ext;
        $attachment->user_id = $userId;
        $attachment->created_time = time();
        $attachment->save();

        return $keys;
    }

    public function ossUploaded($uuid, $userId)
    {
        $attachment = AttachmentModel::where('uuid', $uuid)->first();
        if (!$attachment) {
            throw new ResourceNotFoundException('Attachment not found!');
        }
        if (!$this->getKanbanModule()->isMember($attachment->kanban_id, $userId)) {
            throw new AccessDeniedException();
        }
        $attachment->status = self::STATUS_UPLOADED;
        $attachment->save();

        $this->getKanbanTaskModule()->incrAttachmentNum($attachment->task_id);

        $log = new KanbanTaskLog();
        $log->kanban_id = $attachment->kanban_id;
        $log->task_id = $attachment->task_id;
        $log->user_id = $userId;
        $log->change = json_encode($attachment);
        $log->action = KanbanTask::TASK_LOG_ACTION_ADD_ATTACHMENT;
        $log->created_time = time();
        $log->save();

        return $attachment;
    }

    public function getOssUrl($uuid, $userId)
    {
        $attachment = $this->getByUuid($uuid);
        if (!$this->getKanbanModule()->isMember($attachment->kanban_id, $userId)) {
            throw new AccessDeniedException();
        }
        if ($attachment->storage != self::STORAGE_OSS) {
            throw new BusinessException('Attachment storage is not oss!');
        }
        if ($attachment->status != self::STATUS_UPLOADED) {
            throw new BusinessException('Attachment is uploading, not finished!');
        }
        $cacheKey = sprintf('task:attachment:url:%s', $uuid);
        $url = $this->getCacheRedis()->get($cacheKey);
        if ($url) {
            return $url;
        }
        $oss = OssFactory::aliyun();
        $url = $oss->getDownloadUrl($attachment->file_uri, 300);
        $this->getCacheRedis()->set($cacheKey, $url, null, 300);

        return $url;
    }

    /**
     * $uri 本身具有token效果, 所以此处不做权限验证.
     */
    public function getByUri($uri, array $fields = ['*'])
    {
        return AttachmentModel::where('file_uri', $uri)
            ->where('status', self::STATUS_UPLOADED)
            ->first($fields);
    }

    public function getById($id, array $fields = ['*'])
    {
        return AttachmentModel::where('id', $id)
            ->where('status', self::STATUS_UPLOADED)
            ->first($fields);
    }

    public function getByUuid($uuid, array $fields = ['*'])
    {
        return AttachmentModel::where('uuid', $uuid)
            ->where('status', self::STATUS_UPLOADED)
            ->first($fields);
    }

    public function getTaskAttachments($taskId, $userId, array $fields = ['*'])
    {
        if (!$this->isKanbanMemberByTaskId($userId, $taskId)) {
            throw new AccessDeniedException();
        }

        return AttachmentModel::where('task_id', $taskId)
            ->where('status', self::STATUS_UPLOADED)
            ->orderBy('id', 'DESC')
            ->get($fields);
    }

    public function delete($taskId, $uuid, $userId)
    {
        $task = $this->getKanbanTaskModule()->getTask($taskId, ['kanban_id']);
        if (!$task) {
            throw new ResourceNotFoundException('task.not_found');
        }
        if (!$this->getKanbanModule()->isMember($task->kanban_id, $userId)) {
            throw new AccessDeniedException();
        }
        $attachment = $this->getByUuid($uuid);
        if (!$attachment) {
            return true;
        }

        $log = new KanbanTaskLog();
        $log->kanban_id = $task->kanban_id;
        $log->task_id = $taskId;
        $log->user_id = $userId;
        $log->change = json_encode($attachment);
        $log->action = KanbanTask::TASK_LOG_ACTION_DEL_ATTACHMENT;
        $log->created_time = time();

        $deleted = $attachment->delete();
        if (!$deleted) {
            return false;
        }
        $this->getKanbanTaskModule()->decrAttachmentNum($taskId);
        $log->save();
        return true;
    }

    /**
     * 存在多个同名的文件时，如果都不是从其他卡片复制来的，都展示；如果是复制过来的，只展示非复制的.
     */
    public function searchKanbanAttachmentsForCopy($userId, $kanbanUUid, $keyword = '', $excludeTaskIds = [])
    {
        $kanban = $this->getKanbanModule()->getByUuid($kanbanUUid, ['id']);
        $kanbanId = $kanban->id;
        if (!$this->getKanbanModule()->isMember($kanbanId, $userId)) {
            throw new AccessDeniedException();
        }
        $taskModel = new KanbanTaskModel();
        $taskTable = $taskModel->getTable();

        $attachmentModel = new AttachmentModel();
        $attachmentTable = $attachmentModel->getTable();
        
        $model = Db::table($attachmentTable)
            ->leftJoin($taskTable, $taskTable . '.id', '=', $attachmentTable . '.task_id')
            ->where($taskTable.'.kanban_id', $kanbanId)
            ->where($attachmentTable.'.copy_from_id', 0)
            ->where($attachmentTable.'.status', self::STATUS_UPLOADED);
        if ($excludeTaskIds) {
            $model->whereNotIn($taskTable . '.id', $excludeTaskIds);
        }
        if ($keyword || $keyword === 0 || $keyword === '0') {
            $model->where(function ($query) use ($taskTable, $attachmentTable, $keyword) {
                $query->where($taskTable . '.title', 'like', '%' . $keyword . '%')
                    ->orWhere($attachmentTable . '.org_name', 'like', '%' . $keyword . '%');
            });
        }

        return $model->select($attachmentTable . '.*', $taskTable . '.title AS task_title')
            ->orderBy($attachmentTable . '.org_name')
            ->get();     
    }

    public function copyToTask($id, $taskId, $userId)
    {
        $task = $this->getKanbanTaskModule()->getTask($taskId, ['kanban_id']);
        if (!$task) {
            throw new ResourceNotFoundException('task.not_found');
        }
        if (!$this->getKanbanModule()->isMember($task->kanban_id, $userId)) {
            throw new AccessDeniedException();
        }
        $file = $this->getById($id, ['id', 'kanban_id', 'task_id', 'copy_from_id', 'file_uri', 'size', 'mine_type', 'org_name', 'extension']);
        if (!$file) {
            throw new ResourceNotFoundException('file.not_found');
        }
        if ($file->kanban_id != $task->kanban_id) {
            throw new BusinessException('Copy attahcment can only in the same kanban!');
        }

        if ($file->task_id == $taskId) {
            return $file;
        }

        $newFile = new AttachmentModel();
        $newFile->kanban_id = $file->kanban_id;
        $newFile->copy_from_id = $id;
        $newFile->uuid = Uuid::uuid4()->toString();
        $newFile->file_uri = $file->file_uri;
        $newFile->size = $file->size;
        $newFile->mine_type = $file->mine_type;
        $newFile->org_name = $file->org_name;
        $newFile->extension = $file->extension;
        $newFile->task_id = $taskId;
        $newFile->user_id = $userId;
        $newFile->created_time = time();

        $log = new KanbanTaskLog();
        $log->kanban_id = $task->kanban_id;
        $log->task_id = $taskId;
        $log->user_id = $userId;
        $log->change = json_encode($newFile);
        $log->action = KanbanTask::TASK_LOG_ACTION_ADD_ATTACHMENT;
        $log->created_time = time();

        if ($newFile->save()) {
            $this->getKanbanTaskModule()->incrAttachmentNum($taskId);
            $log->save();
            return $newFile;
        }
        return false;
    }

}
