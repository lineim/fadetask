<?php
namespace app\common\thirdPartClient\dingtalk;

use app\common\thirdPartClient\BaseClient;

class Client extends BaseClient
{
    const ACCESS_TOKEN_CACHE_KEY = 'dingtalk:access_token';

    protected $thirdPart = 'dingtalk';
    protected $baseUrl = 'https://oapi.dingtalk.com/';
    protected $appKey = '';
    protected $appSecret = '';
    protected $loginAppId = '';
    protected $loginAppSecert = '';

    public function setConfig($syncAppKey, $syncAppSecret, $loginAppId, $loginAppSecert)
    {
        $this->appKey = $syncAppKey;
        $this->appSecret = $syncAppSecret;
        $this->loginAppId = $loginAppId;
        $this->loginAppSecert = $loginAppSecert;
    }

    protected function getAccessTokenCacheKey()
    {
        return self::ACCESS_TOKEN_CACHE_KEY;
    }

    public function getAccessTokenFromThirdPart()
    {
        $data = $this->httpGet('/gettoken', ['appkey' => $this->appKey, 'appsecret' => $this->appSecret]);
        $token = $data['access_token'];
        $expiresIn = $data['expires_in'];

        return ['access_token' => $token, 'expires_in' => $expiresIn - 60];
    }

    /**
     * @return ['nick' => string, 'unionid' => string, 'openid' => string]
     */
    public function getUserInfoByCode($code)
    {
        $time = time() * 1000; // 毫秒
        $sign = $this->sha256Sign($time, $this->loginAppSecert);
        $query = [
            'accessKey' => $this->loginAppId,
            'timestamp' => $time,
            'signature' => $sign
        ];
        $body = ['tmp_auth_code' => $code];
        $data = $this->httpPost('/sns/getuserinfo_bycode', $body, $query, [], true);

        return $data['user_info'] ?? [];
    }

    public function getDepartment($id)
    {
        $post = [
            'language' => 'zh_CN',
            'dept_id'  => $id,
        ];
        $data = $this->httpPost('/topapi/v2/department/get', $post, ['access_token' => $this->getAccessToken()]);

        return $data['result'];
    }

    public function getSubDepartmentIds($pid)
    {
        $post = [
            'dept_id'  => $pid,
        ];
        $data = $this->httpPost('/topapi/v2/department/listsubid', $post, ['access_token' => $this->getAccessToken()]);

        return $data['result']['dept_id_list'] ?? [];
    }

    public function getDepartmentUserIds($id)
    {
        $post = [
            'dept_id'  => $id,
        ];
        $data = $this->httpPost('/topapi/user/listid', $post, ['access_token' => $this->getAccessToken()]);

        return $data['result']['userid_list'] ?? [];
    }

    /**
     * @return [
     *  has_more,
     *  next_cursor,
     *  list => []
     * ]
     */
    public function getDepartmentUsers($id, $cursor = 0, $size = 10)
    {
        $post = [
            'dept_id' => $id,
            'cursor' => $cursor,
            'size' => $size
        ];

        $data = $this->httpPost('/topapi/v2/user/list', $post, ['access_token' => $this->getAccessToken()]);
        return $data['result'];
    }

    public function getUser($userId)
    {
        $post = [
            'userid' => $userId,
        ];

        $data = $this->httpPost('/topapi/v2/user/get', $post, ['access_token' => $this->getAccessToken()]);
        return $data['result'];
    }

}
