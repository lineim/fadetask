<?php
namespace process;

use Workerman\Timer;
use support\Db;

class MysqlPing
{
    public function onWorkerStart()
    {
        if (config('unit_test')) { // 跑单元测试时，不能启动Timer，启动会出现 alarm
            return;
        }
        // 每隔5秒检查一次数据库保证mysql链接活跃
        Timer::add(5, function(){
            Db::select("select 1");
        });
    }
}
