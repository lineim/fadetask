<?php
namespace app\common\thirdPartClient\wework;

use app\common\exception\BusinessException;
use app\common\thirdPartClient\BaseClient;

class Client extends BaseClient
{
    const ACCESS_TOKEN_CACHE_KEY = 'wework:access_token';

    protected $thirdPart = 'wework';
    protected $baseUrl = 'https://qyapi.weixin.qq.com';
    protected $appKey = '';
    protected $appSecret = '';
    protected $corpId = '';
    protected $cropSecret = '';

    public function setConfig($appId, $appSecret)
    {
        $this->appKey = $appId;
        $this->appSecret = $appSecret;
    }

    public function setCropInfo($corpId, $cropSecret)
    {
        $this->corpId = $corpId;
        $this->cropSecret = $cropSecret;
    }

    protected function getAccessTokenCacheKey()
    {
        return self::ACCESS_TOKEN_CACHE_KEY;
    }

    public function getAccessTokenFromThirdPart()
    {
        $data = $this->httpGet('/cgi-bin/gettoken', ['corpid' => $this->corpId, 'corpsecret' => $this->cropSecret]);
        $token = $data['access_token'];
        $expiresIn = $data['expires_in'];

        return ['access_token' => $token, 'expires_in' => $expiresIn - 60];
    }

    /**
     * @return ['nick' => string, 'unionid' => string, 'openid' => string]
     */
    public function getUserIdByCode($code)
    {
        $query = [
            'access_token' => $this->getAccessToken(),
            'code' => $code,
        ];
        $data = $this->httpGet('/cgi-bin/user/getuserinfo', $query, []);
        if (!isset($data['UserId'])) {
            throw new BusinessException('用户不属于当前企业');
        }

        return $this->getUser($data['UserId']);
    }

    public function getDepartment($id = null)
    {
        $query = [
            'access_token' => $this->getAccessToken(),
        ];
        if ($id) {
            $query['id'] = $id;
        }
        $data = $this->httpGet('/cgi-bin/department/list', $query);

        return $data['department'];
    }

    public function getSubDepartmentIds($pid)
    {
        throw new BusinessException('not support get sub department');
    }

    public function getDepartmentUserIds($id)
    {
        $query = [
            'access_token' => $this->getAccessToken(),
            'department_id'  => $id,
            'fetch_child' => 0,
        ];
        $data = $this->httpGet('/cgi-bin/user/simplelist', $query);
        $userList = $data['userlist'];
        $userIds = [];
        foreach ($userList as $list) {
            $userIds[] = $list['userid'];
        }

        return $userIds;
    }

    public function getDepartmentUsers($id, $cursor = 0, $size = 10)
    {
        throw new BusinessException('not support get department users');
    }

    public function getUser($userId)
    {
        $query = [
            'userid' => $userId,
            'access_token' => $this->getAccessToken()
        ];

        $user = $this->httpGet('/cgi-bin/user/get', $query);
        return $user;
    }

}
