<?php
namespace app\controller;

use app\common\exception\BusinessException;
use app\common\toolkit\Validator;
use app\module\Notification\Factory;
use support\Request;

class Account extends Base
{

    public function emailAvailable(Request $request)
    {
        $email = $request->post('email', '');
        if (!Validator::email($email)) {
            return $this->json(['code' => 1, 'msg' => '']);
        }
        if ($this->getUserModule()->isEmailUsed($email)) {
            return $this->json(['code' => 1, 'msg' => '邮箱已存在！']);
        }
        return $this->json(['code' => 0]);
    }

    public function mobileAvailable(Request $request)
    {
        $mobile = $request->post('mobile', '');

        if ($this->getUserModule()->isMobileUsed($mobile)) {
            return $this->json(false);
        }
        return $this->json(true);
    }

    public function resetPassEmail(Request $request)
    {
        $email = $request->post('email', '');
        if (!Validator::email($email)) {
            return $this->json(['code' => 1, 'msg' => '邮箱格式错误！']);
        }
        $frontUrl = config('app.kanban_forget_pass_url');
        if (!$frontUrl) {
            throw new BusinessException('front url error');
        }

        $this->getUserModule()->sendResetPasswordEmail($email, $frontUrl);
        return $this->json(['code' => 0, 'msg' => '重置密码链接地址已发送到您的邮箱，请注意查收！']);
    }

    public function resetPass(Request $request)
    {
        $token = $request->post('token');
        $password = $request->post('new_pass');

        $reset = $this->getUserModule()->resetPass($token, $password);

        if ($reset) {
            return $this->json(['success' => 1]);
        }
        return $this->json(['success' => 0, 'msg' => '无效的token！']);
    }

    public function regVerifyCode(Request $request)
    {
        $email = $request->post('email');
        if (!Validator::email($email)) {
            return $this->json(false, 1, '邮箱格式错误！');
        }
        if ($this->getUserModule()->isEmailUsed($email)) {
            return $this->json(false, 1, '邮箱已存在！');
        }
        $exist = $request->session()->get('reg_verify_code');
        if ($exist && time() - $exist['created_at'] < 60) {
            return $this->json(false, 1, sprintf('发送过于频繁，请在%d秒后重发！', 60 - (time() - $exist['created_at'])));
        }
        $code = smsCode(6);
        $request->session()->set('reg_verify_code', ['code' => $code, 'expire_at' => time() + 10 * 60, 'created_at' => time()]);

        $channel = Factory::getChannel('mail');
        $channel->sendRegVerifyCode($email, $code);
        return $this->json(true);
    }

}
