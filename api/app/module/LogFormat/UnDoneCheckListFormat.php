<?php
namespace app\module\LogFormat;


class UnDoneCheckListFormat extends BaseLogFormat 
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
        return sprintf('%s 将检查项 %s 标记为 未完成',  $userName, $checklistName);
    }

}