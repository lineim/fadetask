<?php
namespace app\module\LogFormat;


class CreateFormat extends BaseLogFormat 
{

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
        return sprintf('%s 创建了卡片',  $userName);
    }

}
