<?php
namespace app\module\Notification;

use app\common\exception\BusinessException;
use app\module\Notification\Bridge\SenderBridgeInterface;

class Factory 
{

    protected static $supportChannel = [
        'mail',
        'sms',
        'dingtalk',
        'weword',
        'feishu'
    ];

    /**
     * @return NotificationInterface
     */
    public static function getChannel($channel, $bridge = 'queue', $queueName = 'send_mail')
    {
        $bridge = self::getBridge($bridge, $queueName);
        if (!in_array($channel, self::$supportChannel)) {
            throw new BusinessException(sprintf('notification channel %s not support', $channel));
        }
        $class = 'app\\module\\Notification\\'. ucwords($channel);

        if (!class_exists($class)) {
            throw new BusinessException(sprintf('notification channel class %s not found', $class));
        }
        /**
         * @var NotificationInterface
         */
        return new $class($bridge);
    }

    /**
     * @return SenderBridgeInterface
     */
    protected static function getBridge($bridge, $queueName)
    {
        $class = 'app\\module\\Notification\\Bridge\\'. ucwords($bridge);

        if (!class_exists($class)) {
            throw new BusinessException(sprintf('notification bridge %s not support', $bridge));
        }

        $bridgeInst = new $class;
        if ('queue' == $bridge) {
            if (empty($queueName)) {
                throw new BusinessException(sprintf('bridge %s need queue name', $bridge));
            }
            $bridgeInst->setQueue($queueName);
        }
        return $bridgeInst;
    }

}
