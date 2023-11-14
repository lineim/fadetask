<?php
namespace app\module\LogFormat;


class AddAttachmentFormat extends BaseLogFormat 
{
    protected function subPreprocess($log)
    {
        $change = json_decode($log->change);
        $log->change = $change;
        return $log;
    }

    protected function subInit()
    {
        return ;
    }

    protected function buildMsg($log)
    {
        $userName = isset($this->users[$log->user_id]) ? $this->users[$log->user_id]->name : '';
        $fileName = $log->change->org_name;
        if ($log->change->copy_from_id) {
            $sourceFile = $this->getAttachmentModule()->getById($log->change->copy_from_id, ['task_id']);
            $sourceTask = $this->getKanbanTaskModule()->getTask($sourceFile->task_id, ['title']);

            return sprintf('%s 从卡片【%s】中复制了附件 【%s】',  $userName, $sourceTask->title, $fileName);
        }
        return sprintf('%s 上传了附件 【%s】',  $userName, $fileName);
    }

}