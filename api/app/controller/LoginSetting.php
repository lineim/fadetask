<?php
namespace app\controller;

use app\common\exception\AccessDeniedException;
use app\common\exception\InvalidParamsException;
use support\Request;
use app\controller\Base;

class LoginSetting extends Base
{

    public function get(Request $request)
    {
        return $this->json($this->getSettingModule()->getAllSettings());
    }

    public function adminGet(Request $request, $type)
    {        
        if (!$this->isAdmin($request)) {
            throw new AccessDeniedException('Access Denied!');
        }

        if (!in_array($type, $this->getSettingModule()->getSupportTypes())) {
            throw new InvalidParamsException(sprintf('%s not support'), $type);
        }

        if ($type == 'dingtalk') {
            $setting = $this->getSettingModule()->getDingtalkSetting(true);
        }
        if ($type == 'wework') {
            $setting = $this->getSettingModule()->getWeworkSetting(true);
        }
        
        return $this->json($setting);
    }

    public function put(Request $request, $type)
    {
        if (!$this->isAdmin($request)) {
            throw new AccessDeniedException('Accedd Denied!');
        }
        if (!in_array($type, $this->getSettingModule()->getSupportTypes())) {
            throw new InvalidParamsException(sprintf('%s not support'), $type);
        }

        if ($type == 'dingtalk') {
            $enabled = (bool) $request->post('dingtalk', false); // 转换成bool值
            $corpId = $request->post('corpId', '');
            $loginAppId = $request->post('loginAppId', '');
            $loginAppSecret = $request->post('loginAppSecret', '');
            $syncAppKey = $request->post('syncAppKey', '');
            $syncAppSecret = $request->post('syncAppSecret', '');

            $updated = $this->getSettingModule()->setDingtalkSetting($enabled, $corpId, $loginAppId, $loginAppSecret, $syncAppKey, $syncAppSecret);
            
            $setting = $this->getSettingModule()->getDingtalkSetting(true);
        }
        if ($type == 'wework') {
            $enabled = (bool) $request->post('enabled', false); // 转换成bool值
            $corpId = $request->post('corpId', '');
            $agentId = $request->post('agentId', '');
            $secret = $request->post('secret', '');

            $updated = $this->getSettingModule()->setWeworkSetting($enabled, $corpId, $agentId, $secret);
            
            $setting = $this->getSettingModule()->getWeworkSetting(true);
        }

        
        return $this->json($setting);
    }

}
