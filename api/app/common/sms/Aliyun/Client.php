<?php
namespace app\common\sms\Aliyun;

use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Dysmsapi;

class Client
{

    private static $client;
    private static $endpoint = 'dysmsapi.aliyuncs.com';

    public static function getClient($accessKeyId, $accessKeySecret, $endpoint = '')
    {
        if (!self::$client) {
            $config = new Config([
                "accessKeyId" => $accessKeyId,
                "accessKeySecret" => $accessKeySecret
            ]);
            if ($endpoint) {
                self::$endpoint = $endpoint;
            }
            $config->endpoint = self::$endpoint;
            self::$client = new Dysmsapi($config);
        }
        return self::$client;
    }


    private function __construct()
    {}

    private function __clone()
    {}

}
