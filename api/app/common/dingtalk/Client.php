<?php
namespace app\common\dingtalk;

use app\common\exception\ThirdPartException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\json_decode;
use function GuzzleHttp\Psr7\build_query;
use Psr\Log\LoggerInterface;
use Illuminate\Redis\Connections\Connection;

class Client
{
    const ACCESS_TOKEN_CACHE_KEY = 'dingtalk:access_token';

    static protected $instances;
    protected $httpClient;
    protected $cache;
    protected $logger;
    protected $isDebug = false;
    protected $baseUrl = 'https://oapi.dingtalk.com/';
    protected $appKey = '';
    protected $appSecret = '';
    protected $loginAppId = '';
    protected $loginAppSecert = '';


    public static function inst(LoggerInterface $logger)
    {
        if (!self::$instances) {
            self::$instances = new static($logger);
        }
        return self::$instances;
    }

    private function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->httpClient = new HttpClient([
            'base_uri' => $this->baseUrl,
            'timeout'  => 2.0
        ]);
    }

    public function setConfig($syncAppKey, $syncAppSecret, $loginAppId, $loginAppSecert)
    {
        $this->appKey = $syncAppKey;
        $this->appSecret = $syncAppSecret;
        $this->loginAppId = $loginAppId;
        $this->loginAppSecert = $loginAppSecert;
    }

    public function setCache(Connection $redis)
    {
        $this->cache = $redis;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setDebug($isDebug)
    {
        $this->isDebug = boolval($isDebug);
    }

    public function setHttpClient(HttpClient $client)
    {
        $this->httpClient = $client;
    }

    public function getAccessToken()
    {
        if ($this->cache) {
            $cached = $this->cache->get(self::ACCESS_TOKEN_CACHE_KEY);
            if ($cached) {
                return $cached;
            }
        }
        
        $data = $this->httpGet('/gettoken', ['appkey' => $this->appKey, 'appsecret' => $this->appSecret]);
        $token = $data['access_token'];
        $expiresIn = $data['expires_in'];
        if ($this->cache) {
            $this->cache->set(self::ACCESS_TOKEN_CACHE_KEY, $token, null, $expiresIn - 60);
        }
        return $token;
    }

    /**
     * @return array ['nick' => string, 'unionid' => string, 'openid' => string]
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
     * @return array [
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

    protected function httpGet($uri, $query = [], $options = [])
    {
        if ($this->isDebug) {
            $this->logger->debug('oapi.dingtalk.com request', ['uri' => $uri, 'query' => $query, 'options' => $options]);
        }
        $query = build_query($query);
        $resp = $this->httpClient->request('get', $uri . '?' . $query, $options);

        return $this->parseHttpRes($resp);
    }

    protected function httpPost($uri, $post = [], $query = [], $options = [], $isJson = false)
    {
        if ($this->isDebug) {
            $this->logger->debug('oapi.dingtalk.com request', ['uri' => $uri, 'query' => $query, 'post' => $post, 'options' => $options]);
        }
        $query = build_query($query);
        if (is_string($post)) {
            $postData['body'] = $post; 
        } else {
            if ($isJson) {
                $postData['json'] = $post;
            } else {
                $postData['form_params'] = $post;
            }
        }
        $resp = $this->httpClient->request('post', $uri . '?' . $query, $postData);

        return $this->parseHttpRes($resp);
    }

    protected function parseHttpRes(Response $resp)
    {
        $httpCode = $resp->getStatusCode();
        $headers = $resp->getHeaders();
        $body = $resp->getBody()->getContents();

        if ($this->isDebug) {
            $this->logger->debug('oapi.dingtalk.com response', ['httpCode' => $httpCode, 'headers' => $headers, 'body' => $body]);
        }
        if (200 != $httpCode) {
            $reason = $resp->getReasonPhrase();
            $this->logger->error('oapi.dingtalk.com response error, http code ' . $httpCode, ['httpCode' => $httpCode, 'reason' => $reason, 'headers' => $headers, 'body' => $body]);
            throw new ThirdPartException('request dingtalk api failed, http status is ' . $httpCode);
        }
        $data = json_decode($body, true);
        if (!$data) {
            $this->logger->error('oapi.dingtalk.com response error, body empty or invalid json string', ['httpCode' => $httpCode, 'reason' => 'data empty', 'headers' => $headers, 'body' => $body]);
            throw new ThirdPartException('request dingtalk api failed, response body empty or invalid json string, body is  ' . $body);
        }

        if (isset($data['errcode']) && $data['errcode'] != 0) {
            $this->logger->error('oapi.dingtalk.com response error', $data);
            throw new ThirdPartException(sprintf('request dingtalk api failed, errcode: %d, errmsg: %s', $data['errcode'], $data['errmsg']));
        }
        return $data;
    }

    protected function sha256Sign($string, $loginAppSecret)
    {
        $sign = hash_hmac('sha256', $string, $loginAppSecret, true);
        return (base64_encode($sign));
    }

}
