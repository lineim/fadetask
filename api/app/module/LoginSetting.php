<?php
namespace app\module;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\common\exception\InvalidParamsException;
use app\common\exception\ResourceNotFoundException;
use app\common\toolkit\ArrayHelper;
use \app\model\Kanban as KanbanModel;
use app\model\KanbanList as KanbanListModel;
use \app\model\KanbanMember as KanbanMemberModel;
use Illuminate\Support\Collection;

use app\model\Setting as SettingModel;
use stdClass;

class LoginSetting extends BaseModule
{

    const LOGIN_DINGTALK = 'login_dingtalk';
    const LOGIN_WEWORK = 'login_wework';

    protected $supportTypeToKey = [
        'dingtalk' => self::LOGIN_DINGTALK,
        'wework' => self::LOGIN_WEWORK,
    ];

    public function getSupportTypes()
    {
        return array_keys($this->supportTypeToKey);
    }

    public function getKeyByType($type)
    {
        if (!isset($this->supportTypeToKey[$type])) {
            throw new InvalidParamsException(sprintf('%s not support' , $type));
        }
        return $this->supportTypeToKey[$type];
    }

    public function getAllSettings() : array
    {
        return [
            'dingtalk' => $this->getDingtalkSetting(false),
            'wework' => $this->getWeworkSetting(false),
        ];
    }

    public function getWeworkSetting($withSecret = false) : stdClass
    {
        $setting = SettingModel::where('name', self::LOGIN_WEWORK)->first(['value']);
        if (!$setting) {
            $setting = new stdClass();
            $setting->enabled = false;
            $setting->corpId = '';
            $setting->agentId = '';
            $setting->secret = '';
        } else {
            $setting = json_decode($setting->value);
        }
        
        if (!$withSecret) {
            unset($setting->secret);
        }
        return $setting;
    }

    public function setWeworkSetting(bool $enabled, string $corpId, $agentId, $secret) : bool
    {
        $setting = SettingModel::where('name', self::LOGIN_WEWORK)->first(['value']);

        $config = new stdClass();
        $config->enabled = $enabled ? true : false;
        $config->corpId = $corpId;
        $config->agentId = $agentId;
        $config->secret = $secret;

        if (!$setting) {
            $setting = new SettingModel();
            $setting->name = self::LOGIN_WEWORK;
            $setting->value = json_encode($config);
            $setting->created_time = time();
            $setting->save();
            return true;
        }

        $config = json_decode($setting->value);
        $config->enabled = $enabled ? true : false;
        $config->corpId = $corpId;
        $config->agentId = $agentId;
        $config->secret = $secret;

        $updateData = [
            'value' => json_encode($config),
            'updated_time' => time()
        ];

        SettingModel::where('name', self::LOGIN_WEWORK)->update($updateData);
        return true;
    }

    public function getDingtalkSetting($withSecret = false) : stdClass
    {
        $setting = SettingModel::where('name', self::LOGIN_DINGTALK)->first(['value']);
        if (!$setting) {
            $config = new stdClass();
            $config->enabled = false;
            $config->cropId = '';
            $config->loginAppId  = '';
            $config->loginAppSecret = '';
            $config->syncAppKey = '';
            $config->syncAppSecret = '';
        } else {
            $config = json_decode($setting->value);
        }
        if (!$withSecret) {
            unset($config->loginAppSecret, $config->syncAppSecret);
        }
        return $config;
    }

    public function setDingtalkSetting(bool $enabled, string $corpId, $loginAppId, $loginAppSecret, $syncAppKey, $syncAppSecret) : bool
    {
        $setting = SettingModel::where('name', self::LOGIN_DINGTALK)->first(['value']);

        $config = new stdClass();
        $config->enabled = $enabled ? true : false;
        $config->corpId = $corpId;
        $config->loginAppId = $loginAppId;
        $config->loginAppSecret = $loginAppSecret;
        $config->syncAppKey = $syncAppKey;
        $config->syncAppSecret = $syncAppSecret;

        if (!$setting) {
            $setting = new SettingModel();
            $setting->name = self::LOGIN_DINGTALK;
            $setting->value = json_encode($config);
            $setting->created_time = time();
            $setting->save();
            return true;
        }
        $config = json_decode($setting->value);
        
        $config->enabled = $enabled ? true : false;
        $config->corpId = $corpId;
        $config->loginAppId  = $loginAppId;
        $config->loginAppSecret = $loginAppSecret;
        $config->syncAppKey = $syncAppKey;
        $config->syncAppSecret = $syncAppSecret;

        $updateData = [
            'value' => json_encode($config),
            'updated_time' => time()
        ];

        SettingModel::where('name', self::LOGIN_DINGTALK)->update($updateData);
        return true;
    }

}