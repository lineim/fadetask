<?php
/**
 * This file is part of fade task project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\common\toolkit;

class RedisLock
{

    private $redis;

    private static $instance;

    private function __construct($redis)
    {
        $this->redis = $redis;
    }

    private function __clone()
    {
        
    }

    public static function inst($redis)
    {
        if (!self::$instance) {
            self::$instance = new self($redis);
        }
        return self::$instance;
    }

    public function lock($key, $ttl)
    {
        return $this->redis->set($key, 1, 'ex', $ttl, 'nx');
    }

    public function release($key) 
    {
        return $this->redis->del($key);
    }

}