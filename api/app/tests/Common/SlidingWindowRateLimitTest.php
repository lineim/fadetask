<?php
namespace app\tests\Common;

use app\common\toolkit\SlidingWindowRateLimit as ToolkitSlidingWindowRateLimit;
use app\tests\Base;
use support\bootstrap\Redis;

class SlidingWindowRateLimit extends Base
{

    public function testPerUserLoginLimit()
    {
        $redis = Redis::connection('default');
        $rateLimitor = ToolkitSlidingWindowRateLimit::inst($redis);
        $mobile = '13800110000';

        $timeRange = 5;
        $limitCount = 2;

        $isLimited = $rateLimitor->isLimited($mobile, rand(1, 10000000), $timeRange, $limitCount);
        $this->assertFalse($isLimited);
        sleep(1);
        $isLimited = $rateLimitor->isLimited($mobile, rand(1, 10000000), $timeRange, $limitCount);
        $this->assertFalse($isLimited);
        sleep(1);
        $isLimited = $rateLimitor->isLimited($mobile, rand(1, 10000000), $timeRange, $limitCount);
        $this->assertTrue($isLimited);
        sleep(1);
        $isLimited = $rateLimitor->isLimited($mobile, rand(1, 10000000), $timeRange, $limitCount);
        $this->assertTrue($isLimited);
        sleep(2);
        $isLimited = $rateLimitor->isLimited($mobile, rand(1, 10000000), $timeRange, $limitCount);
        $this->assertFalse($isLimited);
    }

}
