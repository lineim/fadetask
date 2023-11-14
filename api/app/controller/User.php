<?php
namespace app\controller;

use app\common\exception\BusinessException;
use app\common\exception\InvalidParamsException;
use app\common\toolkit\Validator;
use app\module\Notification\Factory;
use app\module\Notification\Mail;
use Ramsey\Uuid\Uuid;
use support\Request;
use support\bootstrap\Log;

class User extends Base
{
    public function reg(Request $request)
    {
        $user = $request->post();
        $code = $user['verify-code'] ?? '';

        if (!$this->verifyRegCode($request, $code)) {
            return $this->json(false, 1, 'Verify Code Error or Expired!');
        }

        $user['verified'] = 1;
        
        try {
            $newUser = $this->getUserModule()->reg($user);
            if ($newUser) {
                return $this->json($newUser);
            }
            return $this->json(false);
        } catch (InvalidParamsException $e) {
            return $this->json([], 500, $e->getMessage());
        } catch (\Exception $e) {
            // todo add log
            Log::error($e->getMessage(), $e->getTrace());
            return $this->json([], 500, "system error");
        }
        
    }

    public function sendRegVerifyCode(Request $request)
    {
        $email = $request->post('email');
        if ($this->getUserModule()->isEmailUsed($email)) {
            return $this->json(false, 1, '邮箱已被使用');
        }
        $code = smsCode(6);
        $redis = $this->getStorageRedis();
        $key = 'reg:' . $email;
        $expireAt = time() + 10*60;
        $data = ['code' => $code, 'expire_at' => $expireAt];

        $redis->hMSet($key, $data);
        $redis->expireAt($expireAt);

        $mailer = Factory::getChannel('mail');
        $mailer->sendRegVerifyCode($email, $code);

        return $this->json(true, 0, '验证码已发送，请前往邮箱查收');
    }

    protected function verifyRegCode(Request $request, $code)
    {
        $session = $request->session();
        $verifyCodeInfo = $session->get('reg_verify_code');

        if (empty($verifyCodeInfo) || 
            time() > $verifyCodeInfo['expire_at'] ||
            $code != $verifyCodeInfo['code']
        ) {
            return false;
        }
        return true;
    }

    public function login(Request $request)
    {
        $username = $request->post('email', '');
        $password = $request->post('password', '');
        $thirdPart = $request->post('third_part', false);

        if ($thirdPart) {
            $type = $request->post('type', '');
            $code = $request->post('code', '');
            // TODO: 判断登录是否开启
            switch ($type) {
                case 'dingtalk':
                    $unionId = $this->getDingtalkModule()->getUnionIdBySnsCode($code);
                    if (!$unionId) {
                        return $this->json([], 600, 'get unionid failed ' . $type);
                    }
                    break;
                    
                case 'wework':
                    $unionId = $this->getWeworkModule()->getUnionIdBySnsCode($code);
                    if (!$unionId) {
                        return $this->json([], 600, 'get unionid failed ' . $type);
                    }
                    break;
                default:
                    return $this->json([], 600, 'not support login type ' . $type);
            }
            $user = $this->getUserModule()->getUserByBind($type, $unionId);
            if (!$user) {
                return $this->json([], 404, 'user not found');
            }
            if (!$user->verified) {
                return $this->json([], 401, 'user not verified');
            }
        } else {
            $user = $this->getUserModule()->getByEmail($username);
            if (!$user) {
                $user = $this->getUserModule()->getByMobile($username);
            }
        }
        // 非第三方登录，校验密码
        if (!$thirdPart && !$this->getUserModule()->login($username, $password, $request->getRealIp(true))) {
            return $this->json([], 600, '邮箱或者密码错误！'); 
        }
        $session = $request->session();
        $token = Uuid::uuid4()->toString();
        $session->set($token, $user);
        return $this->json(['token' => $token, 'user' => $user]);
    }

    public function loginBySms(Request $request)
    {

    }

    public function sendLoginSms(Request $request)
    {
        $mobile = $request->post('mobile');
        if (!Validator::email($mobile)) {
            throw new BusinessException('auth.mobile_error');
        }
        
    }

}
