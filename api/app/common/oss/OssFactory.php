<?php
/**
 * This file is part of fade task project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\common\oss;

use app\common\oss\Aliyun\AliyunOss;

class OssFactory 
{   
    /**
     * 获取阿里云Oss封装接口.
     * 
     * @param string $accessKeyId
     * @param string $accessKeySecret
     * @param string $bucket Bucket.
     * @param string $endpoint 节点.
     * 
     * @return OssInterface
     */
    public static function aliyun()
    {
        $accessKeyId = config('oss.aliyun_oss_access_id');
        $accessKeySecret = config('oss.aliyun_oss_access_secret');
        $bucket = config('oss.aliyun_oss_bucket');
        $endpoint = config('oss.aliyun_oss_endpoint');
        return AliyunOss::instance($accessKeyId, $accessKeySecret, $bucket, $endpoint);
    }
}