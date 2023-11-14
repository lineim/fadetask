<?php
namespace app\module\LogFormat;

class DoneFormat extends BaseLogFormat 
{
    const DONE_TEXT = '已完成';

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
        return sprintf('%s 将卡片标记为 %s', $userName, self::DONE_TEXT);
    }

}