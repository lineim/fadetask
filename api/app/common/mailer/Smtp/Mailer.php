<?php
namespace app\common\mailer\Smtp;

use app\common\mailer\MailerInterface;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

class Mailer implements MailerInterface
{

    protected static $instance;

    protected $server;
    protected $port;
    protected $user;
    protected $password;

    private function __construct($server, $port, $user, $password)
    {
        $this->server = $server;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
    }

    public static function inst($server, $port, $user, $password)
    {
        if (!self::$instance) {
            self::$instance = new static($server, $port, $user, $password);
        }
        return self::$instance;
    }


    public function singleSend($from, $to, $subject, $content, $contentType = 'text/html')
    {
        $transport = $this->transport();
        $mailer = new Swift_Mailer($transport);

        $tos = [];
        if (!is_array($to)) {
            $tos[] = $to;
        } else {
            $tos = $to;
        }

        $message = (new Swift_Message($subject))
        ->setFrom($from)
        ->setTo($tos)
        ->setBody($content, $contentType);

        return $mailer->send($message);
    }

    public function batchSend($from, array $tos, $subject, $content, $contentType = 'text/html')
    {
        return $this->singleSend($from, $tos, $subject, $content, $contentType);
    }

    protected function transport()
    {
        $transport = new Swift_SmtpTransport($this->server, $this->port);
        $transport->setUsername($this->user);
        $transport->setPassword($this->password);

        return $transport;
    }

}
