<?php
namespace app\module\LogFormat;


class AddCheckListFormat extends BaseLogFormat 
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
        $checklistName = $log->change->title;
        return sprintf('%s 增加了检查项 %s',  $userName, $checklistName);
    }

}