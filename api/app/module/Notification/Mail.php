<?php
namespace app\module\Notification;

class Mail extends Base
{

    const CHANNEL = 'mail';

    public function sendRegVerifyCode($to, $code)
    {
        $params['code'] = $code;
        $params['subject'] = 'LKB 注册验证码';

        return $this->sendSingleMsg($to, self::TYPE_REG_VERIFY_CODE, $params);;
    }

    public function sendLoginVerifyCode($to, $code)
    {
        return false;
    }

    public function sendSingleMsg($to, $tplType, array $params)
    {
        $tplContent = $this->loadTpl($tplType);
        $content = $this->replaceVariables($tplContent, $params);
        $data = ['to' => $to, 'subject' => $params['subject'], 'content' => $content];

        return $this->sender->send($data);
    }

    public function sendBatchMsg($tos, $tplType, array $params)
    {
        $tplContent = $this->loadTpl($tplType);
        $content = $this->replaceVariables($tplContent, $params);

        foreach ($tos as $email) {
            $data = ['to' => $email, 'subject' => $params['subject'], 'content' => $content];
            $this->sender->send($data);
        }
        return true;
    }

    public function sendBatchMsgV2($tos, array $params)
    {
        $params['tos'] = $tos;
        $params['mail_version'] = '2.0';
        $this->sender->send($params);
    }

    protected function getChannel()
    {
        return self::CHANNEL;
    }

}
