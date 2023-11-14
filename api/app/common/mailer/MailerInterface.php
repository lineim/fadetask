<?php
namespace app\common\mailer;

interface MailerInterface
{

    public function singleSend($from, $to, $subject, $content, $contentType = 'text/html');

    public function batchSend($from, array $tos, $subject, $content, $contentType = 'text/html');

}
