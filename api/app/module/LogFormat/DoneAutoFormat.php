<?php
namespace app\module\LogFormat;

class DoneAutoFormat extends BaseLogFormat 
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
        return sprintf('卡片被自动标记为 %s', self::DONE_TEXT);
    }

}