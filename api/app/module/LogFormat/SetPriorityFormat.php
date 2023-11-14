<?php
namespace app\module\LogFormat;

class SetPriorityFormat extends BaseLogFormat 
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
        $txt = $this->getKanbanTaskModule()->prioritiesTxt();
        $userName = isset($this->users[$log->user_id]) ? $this->users[$log->user_id]->name : '';
        $priority = $log->change;
        return sprintf('%s 将卡片优先级设置为 %s', $userName, $txt[$priority] ?? '');
    }

}
