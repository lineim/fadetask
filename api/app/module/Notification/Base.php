<?php
namespace app\module\Notification;

use app\common\exception\BusinessException;
use app\module\Notification\Bridge\SenderBridgeInterface;

abstract class Base implements NotificationInterface
{
    const TYPE_REG_VERIFY_CODE = 'reg_verify_code';

    /**
     * @var SenderBridgeInterface
     */
    protected $sender;

    abstract protected function getChannel();

    public function __construct(SenderBridgeInterface $sender)
    {
        $this->setSenderBridge($sender);
    }

    public function setSenderBridge(SenderBridgeInterface $sender)
    {
        $this->sender = $sender;
    }

    protected function loadTpl($type)
    {
        $channel = $this->getChannel();
        $tplPath = __DIR__ . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . $channel . DIRECTORY_SEPARATOR . $type . '.tpl';
        if (!is_file($tplPath)) {
            throw new BusinessException(sprintf('notifaction template not found, channel %s, type %s', $channel, $type));
        }
        return file_get_contents($tplPath);
    }

    protected function replaceVariables($tplContent, array $variables)
    {
        $find = [];
        $replace = [];

        foreach ($variables as $key => $value) {
            $find[] = '{{' . $key . '}}';
            $replace[] = $value;
        }
        return str_replace($find, $replace, $tplContent);
    }

}
