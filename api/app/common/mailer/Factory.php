<?php
namespace app\common\mailer;

use app\common\exception\BusinessException;
use app\common\mailer\MailerInterface;
use app\common\mailer\Smtp\Mailer as SmtpMailer;
use RuntimeException;

class Factory
{
    /**
     * @return MailerInterface
     */
    public static function mailer($type = '')
    {
        $config = config('mailer', []);
        $type = empty($type) ? $config['type'] : $type;
        if ($type && !isset($config[$type])) {
            throw new BusinessException(sprintf('mailer %s not support', $type));
        }
        $config = $config[$type];
        
        switch ($type) {
            case 'smtp':
                $mailer = self::smtp($config);
                break;
            default:
                throw new BusinessException(sprintf('not support mailer %s', $type));
        }
        if (!$mailer instanceof MailerInterface) {
            throw new RuntimeException(sprintf('mailer %s not implements mailer interface', $type));
        }
        return $mailer;
    }

    private static function smtp(array $config)
    {
        return SmtpMailer::inst($config['server'], $config['port'], $config['user'], $config['password']);
    }

}
