<?php
namespace app\module\Notification;

use app\module\Notification\Bridge\SenderBridgeInterface;

interface NotificationInterface
{

    public function sendRegVerifyCode($to, $code);

    public function sendLoginVerifyCode($to, $code);

    public function sendSingleMsg($to, $tplType, array $params);

    public function sendBatchMsg($tos, $tplType, array $params);

    /**
     * 批量发送版本2，异步渲染模板.
     * 
     * @param array $tos    收件人列表.
     * @param array $params 渲染模板参数.
     */
    public function sendBatchMsgV2($tos, array $params);

    public function setSenderBridge(SenderBridgeInterface $sender);

}
