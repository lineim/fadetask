<?php
namespace app\module\LogFormat;


class DelAttachmentFormat extends BaseLogFormat 
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
        return sprintf('%s 删除了附件 %s ',  $userName, $fileName);
    }

}