<?php
/**
 * This file is part of fade task project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\controller;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\common\exception\ResourceNotFoundException;
use app\common\oss\OssFactory;
use support\Request;
use app\controller\Base;
use Webman\Http\UploadFile;
use Workerman\Protocols\Http\Response;

class TaskAttachment extends Base
{

    public function add(Request $request, $taskId)
    {
        $user = $this->getUser($request);
        /**
         * @var UploadFile $file
         */
        $file = $request->file('attachment');
        if (!$file) {
            throw new BusinessException('File Empty');
        }
        $success = $this->getAttachmentModule()->taskAttachment($file, $taskId, $user['id']);

        if (!$success) {
            return $this->json(false, 600, 'task.add.attachment.failed');
        }
        $attachments = $this->getAttachmentModule()->getTaskAttachments($taskId, $user['id']);
        $data['attachments'] = $attachments;
        $data['current'] = $success;
        
        return $this->json($data);
    }

    public function download(Request $request)
    {
        $fileUri = $request->get('file');
        $file = $this->getAttachmentModule()->getByUri($fileUri);

        $range = $request->header('range');
        $ranges = explode('-', substr($range, 6));
        if (isset($ranges[0]) && isset($ranges[1]) && $ranges[0] >=0 && $ranges[0] < $ranges[1]) {
            $end = (int)$ranges[1];
            $start = (int)$ranges[0];
            $len = $end - $start + 1;
            $headers['Content-Disposition'] = "attachment; filename=\"$file->org_name\"";
            return response('', 206)->withFile(\data_path() . $fileUri, $ranges[0], $len);
        }
        
        return response('', 200, [])->download(\data_path() . $fileUri, $file->org_name);
    }

    public function delete(Request $request, $taskId, $uuid)
    {
        $user = $this->getUser($request);

        if ($this->getAttachmentModule()->delete($taskId, $uuid, $user['id'])) {
            return $this->json(true);
        }
        return $this->json(false);
    }

    public function searchForCopy(Request $request, $kanbanId)
    {
        $user = $this->getUser($request);
        $keyword = $request->get('keyword', '');
        $excludeTaskId = $request->get('exclude_task_id');
        $excludeTaskIds = [];
        if ($excludeTaskId) {
            $excludeTaskIds[] = $excludeTaskId;
        }

        $attachments = $this->getAttachmentModule()->searchKanbanAttachmentsForCopy($user['id'], $kanbanId, $keyword, $excludeTaskIds);

        return $this->json($attachments);
    }

    public function copyToTask(Request $request, $fileId)
    {
        $user = $this->getUser($request);
        $toTaskId = $request->post('to_task_id', 0);

        $this->getAttachmentModule()->copyToTask($fileId, $toTaskId, $user['id']);
        $attachments = $this->getAttachmentModule()->getTaskAttachments($toTaskId, $user['id']);

        return $this->json($attachments);
    }

    public function ossUpload(Request $request, $taskId)
    {
        $user = $this->getUser($request);
        $size = $request->post('size', 0);
        $mineType = $request->post('mine_type', '');
        $name = $request->post('name', '');
        $fileArr = explode('.', $name);
        $ext = '';
        if (count($fileArr) > 1) {
            $ext = array_pop($fileArr);
        }

        $info = $this->getAttachmentModule()->ossInit($taskId, $user['id'], $size, $mineType, $name, $ext);
        return $this->json($info);
    }

    public function ossUploadFinished(Request $request)
    {
        $user = $this->getUser($request);
        $uuid = $request->post('uuid');

        $attachment = $this->getAttachmentModule()->ossUploaded($uuid, $user['id']);

        $attachments = $this->getAttachmentModule()->getTaskAttachments($attachment->task_id, $user['id']);
        $data['attachments'] = $attachments;
        $data['current'] = $attachment;

        return $this->json($data);
    }

    public function ossUrl(Request $request, $taskId, $uuid)
    {
        $user = $this->getUser($request);
        $url = $this->getAttachmentModule()->getOssUrl($uuid, $user['id']);
        return $this->json($url);
    }

}
