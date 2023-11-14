<?php
namespace app\module\LogFormat;


class AddMembersFormat extends BaseLogFormat 
{
    protected $addMembersIds = [];
    protected $logMembers = [];
    protected function subPreprocess($log)
    {
        $change = json_decode($log->change);
        $log->change = $change;
        $this->addMembersIds = $change->new_member_ids;

        $this->logMembers[$log->id] = $this->getUsers(array_unique($this->addMembersIds));

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
        $members = $this->logMembers[$log->id] ?? [];
        $memberNames = [];
        foreach ($members as $m) {
            $memberNames[] = $m->name;
        }

        return sprintf('%s 添加了成员 %s',  $userName, implode('，', $memberNames));
    }

}