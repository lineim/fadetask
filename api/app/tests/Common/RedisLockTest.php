<?php
namespace app\tests\Common;

use app\common\toolkit\RedisLock;
use app\tests\Base;
use support\bootstrap\Redis;

class RedisLockTest extends Base
{
    private $key = 'ut:key1';

    protected function setUp(): void
    {
        $this->getLocker()->release($this->key);
    }

    public function testLock()
    {
        $locker = $this->getLocker();
        $this->getRedis()->set($this->key, 1); // Test nx
        $lock = $locker->lock($this->key, 5);
        $this->assertFalse($lock);
        $this->getRedis()->del($this->key);

        $lock = $locker->lock($this->key, 5);
        $this->assertTrue($lock);
        $lockAgain = $locker->lock($this->key, 5);
        $this->assertFalse($lockAgain);
        $locker->release($this->key);
        $lockAgain = $locker->lock($this->key, 5);
        $this->assertTrue($lockAgain);
    }

    public function testTTL()
    {
        $locker = $this->getLocker();
        $lock = $locker->lock($this->key, 2);
        $this->assertTrue($lock);
        $lockAgain = $locker->lock($this->key, 5);
        $this->assertFalse($lockAgain);
        // after ttl
        sleep(3);
        $lock = $locker->lock($this->key, 2);
        $this->assertTrue($lock);
    }

    public function getLocker()
    {
        $redis = Redis::connection('default');
        return RedisLock::inst($redis); 
    }

    public function getRedis()
    {
        return Redis::connection('default');
    }

}
