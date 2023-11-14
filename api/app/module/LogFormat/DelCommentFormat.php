<?php
/**
 * This file is part of fade task project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */

namespace app\module\LogFormat;

class DelCommentFormat extends BaseLogFormat 
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
        $comment = $log->change;
        return sprintf('%s 删除了评论 %s',  $userName, $comment);
    }

}