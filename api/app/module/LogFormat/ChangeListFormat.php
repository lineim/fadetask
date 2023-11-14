<?php
namespace app\module\LogFormat;


class ChangeListFormat extends BaseLogFormat 
{
    protected $listIds = [];
    protected $list = [];

    protected function subPreprocess($log)
    {
        $change = json_decode($log->change, true);

        // 兼容老数据
        $fromList = empty($change['from_list']) ? $change['from'] : $change['from_list'];
        $toList = empty($change['to_list']) ? $change['to'] : $change['to_list'];

        $change['from_list'] = $fromList;
        $change['to_list'] = $toList;
        $log->change = $change;
        $this->listIds[] = $fromList;
        $this->listIds[] = $toList;
        return $log;
    }

    protected function subInit()
    {
        $this->list = $this->getKanbanModule()
            ->getListByIds($this->listIds, ['id', 'name'])
            ->pluck('name', 'id')
            ->toArray();
    }

    protected function buildMsg($log)
    {
        $userName = isset($this->users[$log->user_id]) ? $this->users[$log->user_id]->name : '';
        $formName = isset($this->list[$log->change['from_list']]) ?  $this->list[$log->change['from_list']] : '';
        $toName = isset($this->list[$log->change['to_list']]) ?  $this->list[$log->change['to_list']] : '';
        return sprintf('%s 将卡片从 %s 移动到 %s',  $userName, $formName, $toName);
    }

}
