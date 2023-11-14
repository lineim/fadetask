<?php
namespace app\common\sms;

use app\common\exception\BusinessException;
use app\common\sms\Aliyun\Client;
use app\common\sms\Aliyun\Sms as AliyunSms;
use SmsInterface;

class Factory 
{

    /**
     * @return SmsInterface
     */
    public static function sms()
    {
        $channel = config('sms.channel');
        switch (strtolower($channel)) {
            case 'aliyun':
                return self::aliyun();
            default: 
                throw new BusinessException(sprintf('not support sms channel: %s', $channel));
        }

    }


    /**
     * @return SmsInterface
     */
    public static function aliyun()
    {
        $accessKeyId = config('sms.aliyun_sms_access_id');
        $accessKeySecret = config('sms.aliyun_sms_access_secret');
        $endpoint = config('sms.aliyun_sms_endpoint');
        $signName = config('sms.sign');
        if (empty($accessKeyId) || empty($accessKeySecret)) {
            throw new \Exception('sms config error, please check aliyun_sms_access_id and aliyun_sms_access_secret');
        }

        $client = Client::getClient($accessKeyId, $accessKeySecret, $endpoint);

        return new AliyunSms($client, $signName);
    }

}
