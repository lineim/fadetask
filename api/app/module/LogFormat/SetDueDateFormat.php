<?php
namespace app\module\LogFormat;


class SetDueDateFormat extends BaseLogFormat 
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
        if ($log->change == date('Y-m-d', 0)) {
            return sprintf('%s 清除了截止日期',  $userName);
        }
        $duedate = $log->change;
        return sprintf('%s 将截止日期设置为 %s',  $userName, $duedate);
    }

}
