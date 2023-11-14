<?php
namespace app\common\mailer\Aliyun;

use app\common\mailer\MailerInterface;

class Mailer implements MailerInterface
{
    

    public function singleSend($from, $to, $title, $content, $contentType = 'text/html')
    {

    }

    public function batchSend($from, array $tos, $subject, $content, $contentType = 'text/html')
    {

    }

}
