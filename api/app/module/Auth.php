<?php
namespace app\module;

use app\common\exception\BusinessException;
use app\common\exception\InvalidParamsException;
use app\common\toolkit\Validator;
use app\model\SmsSendLog;
use app\module\Notification\Factory;
use app\model\LoginLog;
use support\bootstrap\Log;

class Auth extends BaseModule
{
    const MODULE_AUTH = 'auth';
    const AUTH_SMS_CODE_INTERVAL = 60;
    const AUTH_CODE_EXPIRE_TIME = 900;
    const FIX_MIN_MAX_TIME = 10;
    const ONE_HOUR_MAX_TIME = 30;

    const LOGIN_ERROR_PARAMS_ERROR = 'MOBILE_LOGIN_PARAMS_ERROR';
    const LOGIN_ERROR_SMSCODE_ERROR = 'MOBILE_LOGIN_SMS_CODE_ERROR';
    const LOGIN_ERROR_REQUEST_TOO_MANY = 'MOBILE_LOGIN_REQUEST_TOO_MANY';

    public function sendSmsAuthCode($mobile)
    {
        if (!Validator::mobile($mobile)) {
            throw new BusinessException('auth.mobile_error');
        }
        $logger = Log::channel('sms');

        $redis = $this->getStorageRedis();
        $key = $this->getAuthCodeStorageKey($mobile);

        $this->canSendAuthCode($mobile);

        if ($this->isDangerMobile($mobile)) {
            throw new BusinessException('auth.sms_send_too_many');
        }

        $expireAt = time() + self::AUTH_CODE_EXPIRE_TIME;
        $code = smsCode();
        $data = ['code' => $code, 'expire_at' => $expireAt, 'send_time' => time()];

        $redis->hMSet($key, $data);
        $redis->expireAt($key, $expireAt);

        try {
            $sender = Factory::getChannel('sms', 'queue', 'send_sms');
            $sender->sendLoginVerifyCode($mobile, $code);
        } catch (\Exception $e) {
            $logger->error('send sms error', $e->getMessage());
            throw $e;
        }
        SmsSendLog::insert([
            'mobile' => $mobile,
            'module' => self::MODULE_AUTH,
            'params' => json_encode($data),
            'created_time' => time()
        ]);
        
        return true;
    }

    public function loginByCode($mobile, $code, $remoteIp)
    {
        $log = new LoginLog();
        $log->login_ip = substr($remoteIp ?? '', 0, 256);
        $log->created_time = time();
        $params = ['mobile' => $mobile, 'code' => $code];
        
        $user = $this->getUserModule()->getByMobile($mobile);
        if ($user) {
            $log->user_id = $user->id;
        }
        if (!Validator::mobile($mobile) || empty($code)) {
            $log->is_success = 0;
            $log->error_type = self::LOGIN_ERROR_PARAMS_ERROR;
            $log->params = json_encode($params);
            $log->save();
            throw new InvalidParamsException('auth.params_error');
        }
        $rateLimit = $this->getRateLimit();
        if ($rateLimit->isLimited($mobile, rand(0, 9999999), 300, 5, 600)) {
            $log->is_success = 0;
            $log->error_type = self::LOGIN_ERROR_REQUEST_TOO_MANY;
            $log->params = json_encode($params);
            $log->save();
            throw new BusinessException('system.request_too_fast');
        }
        $existCode = $this->getSendedAuthCode($mobile);
        if (!$existCode || $existCode['expire_at'] < time() || $code != $existCode['code']) {
            $log->is_success = 0;
            $log->error_type = self::LOGIN_ERROR_SMSCODE_ERROR;
            $params['exist_code'] = $existCode;
            $log->params = json_encode($params);
            $log->save();
            throw new BusinessException('auth.sms_code_error');
        }
        $this->delAuthCode($mobile);
        if (!$user) {
            $user = [
                'name' => mb_substr($mobile, -4),
                'mobile' => $mobile,
                'password' => uniqid(time()),
                'reg_type' => 'mobile',
                'verified' => 1,
            ];
            $this->getUserModule()->reg($user);
            $user = $this->getUserModule()->getByMobile($mobile);
        }
        $log->user_id = $user->id;
        $log->params = json_encode($params);
        $log->is_success = 1;
        $log->save();
        return $user;
    }

    public function canSendAuthCode($mobile)
    {
        $record = $this->getSendedAuthCode($mobile);
        if (!empty($record['send_time']) && $record['send_time'] > time() - self::AUTH_SMS_CODE_INTERVAL) {
            throw new BusinessException('auth.sms_send_too_quick');
        }
        return true;
    }

    public function getSendedAuthCode($mobile)
    {
        $redis = $this->getStorageRedis();
        return $redis->hGetAll($this->getAuthCodeStorageKey($mobile));
    }

    public function delAuthCode($mobile) 
    {
        $redis = $this->getStorageRedis();
        return $redis->del($this->getAuthCodeStorageKey($mobile));
    }

    public function isDangerMobile($mobile) 
    {
        $count = SmsSendLog::where('mobile', $mobile)
            ->where('module', self::MODULE_AUTH)
            ->where('created_time', '>=', time() - 10*60)
            ->count();
        if ($count > 5) {
            return true;
        }

        $count = SmsSendLog::where('mobile', $mobile)
            ->where('module', self::MODULE_AUTH)
            ->where('created_time', '>=', time() - 3600)
            ->count();
        if ($count > 10) {
            return true;
        }
        return false;
    }

    public function getLastAuthSendTime($mobile)
    {
        return SmsSendLog::where('mobile', $mobile)
            ->where('module', self::MODULE_AUTH)
            ->orderBy('id', 'DESC')
            ->first(['created_time']);
    }

    protected function getAuthCodeStorageKey($mobile)
    {
        return 'auth:sms:code' . $mobile;
    }

}
