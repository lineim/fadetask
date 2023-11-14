<?php
namespace app\module;

use app\common\exception\BusinessException;
use app\common\exception\InvalidParamsException;
use app\common\exception\ResourceNotFoundException;
use app\common\mailer\Factory;
use app\common\toolkit\ArrayHelper;
use app\common\toolkit\Validator;
use app\model\LoginLog;
use app\model\User as UserModel;
use app\model\UserBind;
use app\module\Notification\Factory as NotificationFactory;
use Dotenv\Exception\InvalidPathException;
use Ramsey\Uuid\Uuid;

class User extends BaseModule
{
    const VERIFIED = 1;
    const UN_VERIFIED = 0;

    const MAX_USERNAME_LEN = 8;

    const LOGIN_ERROR_TYPE_USER_NOT_FOUND = 'USER_NOT_FOUND';
    const LOGIN_ERROR_TYPE_PASSWORD_ERROR = 'PASSWORD_ERROR';
    const LOGIN_ERROR_TYPE_USER_INVALID = 'USER_INVALID';
    const LOGIN_ERROR_TYPE_REQUEST_TOO_MANY = 'REQUEST_TOO_MANY';

    public function getByUserId($id, array $fields = ['*'])
    {
        $user = UserModel::where('id', (int) $id)->first($fields);
        unset($user->passhash);
        return $user;
    }

    public function getByUserIds(array $ids, array $fields = ['*'], $offset = 0, $limit = 0)
    {
        if (!$ids || !is_array($ids)) {
            throw new InvalidParamsException("Invalid User Ids!");
        }
        $userModel = UserModel::whereIn('id', $ids);
        if ($limit > 0) {
            $userModel->offset($offset)->limit($limit);
        }
        $users = $userModel->orderBy('id', 'desc')->get($fields);
        foreach ($users as &$user) {
            unset($user->passhash);
        }
        return $users;
    }

    public function getByEmails(array $emails, array $fields = ['*'])
    {
        if (!$emails || !is_array($emails)) {
            throw new InvalidParamsException("Invalid User emails!");
        }
        return UserModel::whereIn('email', $emails)->get($fields);
    }

    public function getNotExistEmails(array $emails)
    {
        $users = $this->getByEmails($emails, ['id', 'email']);
        $existEmails = $users->pluck('email')->toArray();
        $notExistEmails = [];

        foreach ($emails as $email) {
            if (!in_array($email, $existEmails)) {
                $notExistEmails[] = $email;
            }
        }
        return $notExistEmails;
    }

    public function getCountByUserIds(array $ids)
    {
        if (!$ids) {
            throw new InvalidParamsException("Invalid User Ids!");
        }
        return UserModel::whereIn('id', $ids)->count();
    }

    public function isSysAdmin($id)
    {
        $role = UserModel::where('id', $id)->first(['role']);
        if (!$role) {
            return false;
        }

        return UserModel::ROLE_ADMIN == $role->role;
    }

    public function markAsSysAdmin($id)
    {
        $user = $this->getByUserId($id, ['role']);
        if (empty($user)) {
            throw new BusinessException('user not found');
        }
        if (UserModel::ROLE_ADMIN == $user->role) {
            return true;
        }
        return $this->updateUser($id, ['role' => UserModel::ROLE_ADMIN]);
    }

    public function isEmailUsed($email)
    {
        return UserModel::where('email', $email)->exists();
    }

    public function isMobileUsed($mobile)
    {
        return UserModel::where('mobile', $mobile)->exists();
    }

    public function reg(array $user)
    {
        $this->verifyReg($user);

        $locker = $this->getLocker();

        $email = $user['email'] ?? '';
        $mobile = $user['mobile'] ?? '';
        $passowrd = $user['password'];
        $name = $user['name'];
        $title = $user['title'] ?? '';
        $hiredTime = $user['hired_time'] ?? 0;
        $verified = $user['verified'] ?? 0;
        $regType = $user['reg_type'] ?? 'email';

        $key = sprintf('reg:%s:%s', $email, $mobile);
        if ($this->getRateLimit()->isLimited($key, rand(1, 999999), 300, 5, 600)) {
            throw new BusinessException('system.request_too_fast');
        }
        if (!$this->getLocker()->lock($key, 5)) {
            throw new BusinessException('Too many request!');
        }

        $passwordHash = password_hash(trim($passowrd), PASSWORD_BCRYPT);
        $newUser = new UserModel();
        $uuid = Uuid::uuid4();
        $newUser->uuid = $uuid->toString();
        $newUser->email = trim($email);
        $newUser->mobile = $mobile;
        $newUser->reg_type = $regType;
        $newUser->hired_time = $hiredTime;
        $newUser->title = $title;
        $newUser->passhash = $passwordHash;
        $newUser->name = $name;
        $newUser->verified = $verified;
        $newUser->avatar = '';
        $newUser->created_time = time();

        $result = false;
        try {
            $result = $newUser->save();
        } finally {
            $locker->release($key);
            return $result;
        }
    }

    public function updateUser($id, array $updateData)
    {
        $canUpdateFields = ['name', 'email', 'mobile', 'passhash', 'role', 'reg_type', 'hired_time', 'title', 'verified', 'avatar'];
        $updateData = ArrayHelper::parts($updateData, $canUpdateFields);
        if (!empty($updateData['name']) && mb_strlen($updateData['name']) > self::MAX_USERNAME_LEN) {
            throw new InvalidParamsException("user.name.invalid");
        }
        return UserModel::where('id', $id)->update($updateData);
    }

    public function login($username, $password, $remoteIp)
    {
        $log = new LoginLog();
        $log->login_ip = substr($remoteIp ?? '', 0, 256);
        $log->created_time = time();

        $user = $this->getByEmail($username);
        if (!$user) {
            $user = $this->getByMobile($username);
        }
        if (!$user) {
            $log->is_success = 0;
            $log->error_type = self::LOGIN_ERROR_TYPE_USER_NOT_FOUND;
            $log->save();
            return false;
        }
        if ($this->getRateLimit()->isLimited($username, rand(1, 999999), 300, 5, 600)) {
            $log->is_success = 0;
            $log->error_type = self::LOGIN_ERROR_TYPE_REQUEST_TOO_MANY;
            $log->save();
            throw new BusinessException('system.request_too_fast');
        }
        $log->user_id = $user->id;
        if (!$user->verified) {
            $log->is_success = 0;
            $log->error_type = self::LOGIN_ERROR_TYPE_USER_INVALID;
            $log->save();
            throw new BusinessException('user.invalid');
        }
        if (!password_verify($password, $user->passhash)) {
            $log->is_success = 0;
            $log->error_type = self::LOGIN_ERROR_TYPE_PASSWORD_ERROR;
            $log->save();
            return false;
        }
        $log->is_success = 1;
        $log->save();
        return true;
    }

    /**
     * 1分钟内连续登陆失败5次，算做风险账号.
     */
    public function isLoginDanger($userId)
    {
        $logs = LoginLog::where('user_id', $userId)
            ->where('created_time', '>', time() - 1*60)
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get(['is_success']);

        if (count($logs) < 5) {
            return false;
        }

        $danger = true;
        foreach ($logs as $log) {
            if ($log->is_success) {
                $danger = false;
            }
        }
        return $danger;
    }

    public function getLoginLogs(array $conditions, $offset, $limit, $fields = ['*'])
    {
        $modal = new LoginLog();
        foreach ($conditions as $key => $val) {
            $modal->where($key, $val);
        }
        return $modal->orderBy('id', 'DESC')
            ->offset($offset)
            ->limit($limit)
            ->get($fields);
    }

    public function getLoginLogsCount(array $conditions)
    {
        $modal = new LoginLog();
        foreach ($conditions as $key => $val) {
            $modal->where($key, $val);
        }
        return $modal->count();
    }

    public function getByEmail($email, array $fields = ['*'])
    {
        return UserModel::where('email', $email)->first($fields);
    }

    public function getByMobile($mobile, array $fields = ['*'])
    {
        return UserModel::where('mobile', $mobile)->first($fields);
    }

    public function getByUuid($uuid, array $fields = ['*'])
    {
        return UserModel::where('uuid', $uuid)->first($fields);
    }

    public function emailAvailableForUpdate($uuid, $email)
    {
        $user = $this->getByUuid($uuid);
        if (!$user) {
            throw new BusinessException('user not found');
        }
        $userByEmail = $this->getByEmail($email, ['uuid']);
        if (!$userByEmail) {
            return true;
        }
        return $userByEmail->uuid === $uuid;
    }

    public function mobileAvailableForUpdate($uuid, $mobile)
    {
        $user = $this->getByUuid($uuid);
        if (!$user) {
            throw new BusinessException('user not found');
        }
        $userByMobile = $this->getByMobile($mobile, ['uuid']);
        if (!$userByMobile) {
            return true;
        }
        return $userByMobile->uuid === $uuid;
    }

    public function getUserByBind($type, $unionId, array $fields = ['*'])
    {
        $bind = UserBind::where('type', $type) ->where('union_id', $unionId)->first('user_id');
        if (!$bind) {
            return false;
        }
        return UserModel::where('id', $bind->user_id)->first($fields);
    }

    public function markVerified($uuid)
    {
        $user = $this->getByUuid($uuid, ['verified']);
        if (!$user) {
            throw new BusinessException('user not exist!');
        }
        if ($user->verified == self::VERIFIED) {
            return true;
        }
        $verified = $this->updateUserByUuid($uuid, ['verified' => self::VERIFIED]);
        return (bool) $verified;
    }

    public function markUnverified($uuid)
    {
        $user = $this->getByUuid($uuid, ['verified']);
        if (!$user) {
            throw new BusinessException('user not exist!');
        }
        if ($user->verified == self::UN_VERIFIED) {
            return true;
        }

        $verified = $this->updateUserByUuid($uuid, ['verified' => self::UN_VERIFIED]);
        return (bool) $verified;
    }

    public function resetPass($token, $newPassword)
    {
        if (!Validator::password($newPassword)) {
            return false;
        }
        $redis = $this->getStorageRedis();
        $tokenInfo = $redis->hGetAll($token);
        if (!$tokenInfo) {
            return false;
        }
        $expireAt = $tokenInfo['expire_at'] ?? 0;
        if (time() > $expireAt) {
            return false;
        }
        
        $email = isset($tokenInfo['email']) ? $tokenInfo['email']  : '';
        if (!$email) {
            return false;
        }
        $user = $this->getByEmail($email, ['uuid']);
        if (!$user) {
            return false;
        }
        
        return (bool) $this->updatePassword($user->uuid, $newPassword);
    }

    public function sendResetPasswordEmail($email, $frontUrl)
    {
        if (!$this->isEmailUsed($email)) {
            throw new BusinessException('邮箱不存在！');
        }
        $redis = $this->getStorageRedis();
        $token = hash('sha512', Uuid::uuid4()->toString());
        $expireAt = time() + 10*60;
        $tokenInfo = [
            'email' => $email,
            'expire_at' => $expireAt
        ];
        $redis->hMSet($token, $tokenInfo);
        $redis->expireAt($token, $expireAt);

        $channel = NotificationFactory::getChannel('mail');
        $channel->sendSingleMsg(
            $email, 
            'forget_password', 
            [
                'subject' => '重置密码',
                'url' => $frontUrl . '?' . http_build_query(['token' => $token]),
            ]
        );
        return true;
    }

    public function updatePassword($uuid, $newPassword)
    {
        $user = $this->getByUuid($uuid, ['id', 'passhash']);
        if (!$user) {
            throw new BusinessException('user not found!');
        }
        if (!Validator::password($newPassword)) {
            throw new InvalidParamsException('password format error');
        }
        $newPassHash = password_hash($newPassword, PASSWORD_BCRYPT);

        $update = $this->updateUser($user->id, ['passhash' => $newPassHash]);

        return (bool) $update;
    }

    public function updatePasswordByOldPassword($uuid, $oldPass, $newPass)
    {
        $user = $this->getByUuid($uuid, ['passhash', 'uuid']);
        if (!$user) {
            throw new ResourceNotFoundException('user not found');
        }
        if (!password_verify($oldPass, $user->passhash)) {
            throw new BusinessException('当前密码不正确！');
        }

        return $this->updatePassword($user->uuid, $newPass);
    }

    protected function updateUserByUuid($uuid, array $updateFields)
    {
        return UserModel::where('uuid', $uuid)->update($updateFields);
    }

    protected function verifyReg(array $user)
    {
        if (empty($user['email']) && empty($user['mobile'])) {
            throw new InvalidParamsException('email or mobile required!');
        }
        if (isset($user['email']) && !filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidParamsException('invalid email!');
        }
        if (isset($user['email']) && $this->isEmailUsed($user['email'])) {
            throw new InvalidParamsException('email used!');
        }
        if (isset($user['mobile']) && !Validator::mobile($user['mobile'])) {
            throw new InvalidParamsException('invalid mobile!');
        }
        if (isset($user['mobile']) && $this->isMobileUsed($user['mobile'])) {
            throw new InvalidParamsException('mobile used!');
        }
        if (!Validator::password($user['password'])) {
            throw new InvalidParamsException("invalid password!");
        }
        if (empty($user['name']) || mb_strlen($user['name']) > self::MAX_USERNAME_LEN) {
            throw new InvalidParamsException("user.name.invalid");
        }
        if (isset($user['email']) && $this->getByEmail($user['email'], ['id'])) {
            throw new InvalidParamsException('email used!');
        }
        if (isset($user['mobile']) && $this->getByMobile($user['mobile'], ['id'])) {
            throw new InvalidParamsException('mobile used!');
        }
        if (isset($user['reg_type']) && !in_array($user['reg_type'], ['email', 'mobile', 'dingtalk', 'wework', 'feishu'])) {
            throw new InvalidParamsException('reg type error!');
        }
    }
}
