<?php
namespace app\module\LogFormat;


class RemMembersFormat extends BaseLogFormat 
{
    protected $addMembersIds = [];
    protected $members = [];
    protected function subPreprocess($log)
    {
        $change = json_decode($log->change);
        $log->change = $change;
        $this->addMembersIds = $change->remove_member_ids;
        return $log;
    }

    protected function subInit()
    {
        if ($uids = array_unique($this->addMembersIds)) {
            $this->members = $this->getUsers($uids);
        }
    }

    protected function buildMsg($log)
    {
        $userName = isset($this->users[$log->user_id]) ? $this->users[$log->user_id]->name : '';
        $memberNames = [];
        foreach ($this->members as $m) {
            $memberNames[] = $m->name;
        }
        return sprintf('%s 移除了成员 %s',  $userName, implode('，', $memberNames));
    }

}