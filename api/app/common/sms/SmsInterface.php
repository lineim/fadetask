<?php
namespace app\common\sms;

interface SmsInterface
{

    public function setClient($client);

    public function send($mobile, $tplCode, array $params);

}
