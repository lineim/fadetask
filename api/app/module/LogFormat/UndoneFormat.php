<?php
namespace app\module\LogFormat;

class UndoneFormat extends BaseLogFormat 
{
    const UNDONE_TEXT = '未完成';

    protected function subPreprocess($log)
    {
        return $log;
    }

    protected function subInit()
    {
        return ;
    }

    protected function buildMsg($log)
    {
        $userName = isset($this->users[$log->user_id]) ? $this->users[$log->user_id]->name : '';
        return sprintf('%s 将卡片标记为 %s', $userName, self::UNDONE_TEXT);
    }

}