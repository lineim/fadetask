<?php
namespace app\module\LogFormat;

use app\common\toolkit\ModuleTrait;
use app\common\toolkit\DateTime;

abstract class BaseLogFormat 
{
    use ModuleTrait;

    protected $uids = [];
    protected $users = [];
    protected $logs = [];

    abstract protected function subPreprocess($log);
    abstract protected function subInit();
    abstract protected function buildMsg($log);

    public function process($logs)
    {
        $logs = $this->preprocess($logs);
        $this->init();

        foreach ($logs as &$log) {
            $log->show_msg = $this->buildMsg($log);
            $log->user = $this->users[$log->user_id] ?? new \stdClass();
            $log->date = date('Y-m-d H:i:s', $log->created_time);
            $log->sample_date = DateTime::sampleDate($log->created_time);
        }
        return $logs;
    }

    protected function preprocess($logs)
    {
        $uids = [];
        foreach ($logs as &$log) {
            $uids[] = $log->user_id;
            $log = $this->subPreprocess($log);
        }
        $this->uids = $uids;
        return $logs;
    }

    protected function init()
    {

        $this->users = $this->getUsers($this->uids);
        $this->subInit();
    }

    protected function getUsers(array $uids)
    {
        $users = [];
        if ($uids) {
            $users = $this->getUserModule()->getByUserIds($uids, ['id', 'name', 'avatar']);
        }
        $indexUsers = [];
        foreach ($users as $key => $user) {
            unset($users[$key]);
            $indexUsers[$user->id] = $user;
        }
        unset($users);
        return $indexUsers;
    }

    protected function getLogs()
    {
        return $this->logs;
    }

    protected function format(&$log)
    {

    }

}