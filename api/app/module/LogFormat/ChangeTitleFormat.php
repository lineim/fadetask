<?php
namespace app\module\LogFormat;


class ChangeTitleFormat extends BaseLogFormat 
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
        $from = $log->change->from;
        $to = $log->change->to;
        return sprintf('%s 将标题从【%s】修改为 【%s】',  $userName, $from, $to);
    }

}