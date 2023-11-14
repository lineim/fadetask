<?php
/**
 * This file is part of fade task project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */

namespace app\module\LogFormat;

class EditCommentFormat extends BaseLogFormat 
{
    protected function subPreprocess($log)
    {
        $log->change = @json_decode($log->change, true);
        return $log;
    }

    protected function subInit()
    {
        return ;
    }

    protected function buildMsg($log)
    {
        $userName = isset($this->users[$log->user_id]) ? $this->users[$log->user_id]->name : '';
        $origin = $log->change['origin'] ?? '';
        $new = $log->change['new'] ?? '';
        return sprintf('%s 将评论 %s 修改为 %s',  $userName, $origin, $new);
    }

}