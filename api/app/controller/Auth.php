<?php
namespace app\controller;

use support\Request;
use Ramsey\Uuid\Uuid;

class Auth extends Base
{

    public function auth(Request $request)
    {
        $email = $request->post('email');
        $password = $request->post('password');
        // todo login in db

        $token = uniqid();
        $user = $this->getUser($request);

        $session = $request->session();
        $session->set($token, $user);

        return $this->json(['token' => $token, 'user' => $user]);
    }

    public function sendSmsLogin(Request $request)
    {
        $mobile = $request->post('mobile');

        return $this->json($this->getAuthModule()->sendSmsAuthCode($mobile));
    }

    public function loginByCode(Request $request)
    {
        $mobile = $request->post('mobile', '');
        $code = $request->post('code', '');

        $user = $this->getAuthModule()->loginByCode($mobile, $code, $request->getRealIp(true));
        $session = $request->session();
        $token = Uuid::uuid4()->toString();
        $session->set($token, $user);
        return $this->json(['token' => $token, 'user' => $user]);
    }

}
