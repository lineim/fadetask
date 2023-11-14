<?php
namespace app\module\Notification;

class Sms extends Base
{

    const CHANNEL = 'sms';

    public function sendLoginVerifyCode($to, $code)
    {
        return $this->sendAuthCode($to, $code);
    }

    public function sendRegVerifyCode($to, $code)
    {
        return $this->sendAuthCode($to, $code);
    }

    public function sendSingleMsg($to, $tplType, array $params)
    {
        
    }

    public function sendBatchMsg($tos, $tplType, array $params)
    {
        
    }

    public function sendBatchMsgV2($tos, array $params)
    {
        
    }

    public function sendAuthCode($to, $code) 
    {
        $tplCode = config('sms.auth_tpl_code');
        return $this->sender->send(['to' => $to, 'code' => $code, 'tplCode' => $tplCode]);
    }

    protected function getChannel()
    {
        return self::CHANNEL;
    }
}
