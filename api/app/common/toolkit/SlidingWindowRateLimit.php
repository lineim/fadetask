<?php
namespace app\common\toolkit;

class SlidingWindowRateLimit
{
    const PREFIX = 'SLIDINGWINRATELIMIT';
    protected static $instance = [];
    private $redis;

    private function __construct($redis)
    {
        $this->redis = $redis;
    }

    public static function inst($redis)
    {
        if (!self::$instance) {
            self::$instance = new self($redis);
        }
        return self::$instance;
    }

    /**
     * @param string $limitName Rate Limit Name.
     * @param string $uuid User UUid.
     * @param integer $timeRange 限流时间, 微妙.
     * @param integer $limitCount 限制次数.
     * 
     * @return boolean
     */
    public function isLimited($limitName, $uuid, $timeRange, $limitCount, $ttl = 300)
    {
        $nowTime = microtime(true) * 10000;
        $startTime = $nowTime - $timeRange * 10000;

        $zKey = self::PREFIX . ':' . $limitName;

        $requestHistory = $this->redis->zRangeByScore($zKey, $startTime, $nowTime);
        if (count($requestHistory) + 1 > $limitCount) {
            return true;
        }

        $this->redis->multi();
        // delete the data which out of sliding window
        $this->redis->zRemRangeByScore($zKey, 0, $startTime);
        $this->redis->zAdd($zKey, [], $nowTime, $uuid);
        $this->redis->expire($zKey, $ttl);
        $this->redis->exec();

        return false;
    }

}
