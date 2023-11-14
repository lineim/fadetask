<?php
/**
 * This file is part of fadetask kanban project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\module\LogFormat;
use app\module\LogFormat\BaseLogFormat;

class MoveToOtherFormat extends BaseLogFormat
{
    private $fromKanban;
    private $toKanban;

    protected function subPreprocess($log)
    {
        $changes = @json_decode($log->change, true);
        if (empty($changes)) {
            return;
        }
        $this->fromKanban = $this->getKanbanModule()->get($changes['from_kanban'], ['name']);
        $this->toKanban = $this->getKanbanModule()->get($changes['to_kanban'], ['name']);
    }

    protected function subInit()
    {
    }
    
    protected function buildMsg($log)
    {
        if ($this->fromKanban && $this->toKanban) {
            $userName = isset($this->users[$log->user_id]) ? $this->users[$log->user_id]->name : '';
            return sprintf('%s 将卡片从看板【%s】移动到看板【%s】', $userName, $this->fromKanban->name, $this->toKanban->name);
        }
        return '';
    }

}
