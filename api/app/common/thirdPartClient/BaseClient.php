<?php
namespace app\common\thirdPartClient;

use app\common\exception\ThirdPartException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\json_decode;
use function GuzzleHttp\Psr7\build_query;
use Psr\Log\LoggerInterface;
use Illuminate\Redis\Connections\Connection;

abstract class BaseClient
{
    const ACCESS_TOKEN_CACHE_KEY = 'dingtalk:access_token';

    static protected $instances;
    protected $httpClient;
    protected $cache;
    protected $logger;
    protected $isDebug = false;
    protected $thirdPart = '';
    
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

    abstract protected function getAccessTokenCacheKey();
    abstract public function getAccessTokenFromThirdPart();

    public function getAccessToken()
    {
        if ($this->cache && $cached = $this->cache->get($this->getAccessTokenCacheKey())) {
            return $cached;
        }
        
        $data = $this->getAccessTokenFromThirdPart();
        $token = $data['access_token'];
        $expiresIn = $data['expires_in'];
        if ($this->cache) {
            $this->cache->set($this->getAccessTokenCacheKey(), $token, null, $expiresIn);
            $this->cache->expire($this->getAccessTokenCacheKey(), $expiresIn);
        }
        return $token;
    }

    protected function httpGet($uri, $query = [], $options = [])
    {
        if ($this->isDebug) {
            $this->logger->debug('[thirdpart]['.$this->thirdPart.'] request', ['uri' => $uri, 'query' => $query, 'options' => $options]);
        }
        $query = build_query($query);
        $resp = $this->httpClient->request('get', $uri . '?' . $query, $options);

        return $this->parseHttpRes($resp);
    }

    protected function httpPost($uri, $post = [], $query = [], $options = [], $isJson = false)
    {
        if ($this->isDebug) {
            $this->logger->debug('[thirdpart]['.$this->thirdPart.'] request', ['uri' => $uri, 'query' => $query, 'post' => $post, 'options' => $options]);
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
            $this->logger->debug('[thirdpart]['.$this->thirdPart.'] response', ['httpCode' => $httpCode, 'headers' => $headers, 'body' => $body]);
        }
        if (200 != $httpCode) {
            $reason = $resp->getReasonPhrase();
            $this->logger->error('[thirdpart]['.$this->thirdPart.'] response error, http code ' . $httpCode, ['httpCode' => $httpCode, 'reason' => $reason, 'headers' => $headers, 'body' => $body]);
            throw new ThirdPartException('request '.$this->thirdPart.' api failed, http status is ' . $httpCode);
        }
        $data = json_decode($body, true);
        if (!$data) {
            $this->logger->error('[thirdpart]['.$this->thirdPart.'] response error, body empty or invalid json string', ['httpCode' => $httpCode, 'reason' => 'data empty', 'headers' => $headers, 'body' => $body]);
            throw new ThirdPartException('request '.$this->thirdPart.' api failed, response body empty or invalid json string, body is  ' . $body);
        }

        if (isset($data['errcode']) && $data['errcode'] != 0) {
            $this->logger->error('[thirdpart]['.$this->thirdPart.'] response error', $data);
            throw new ThirdPartException(sprintf('request '.$this->thirdPart.' api failed, errcode: %d, errmsg: %s', $data['errcode'], $data['errmsg']));
        }
        return $data;
    }

    protected function sha256Sign($string, $loginAppSecret)
    {
        $sign = hash_hmac('sha256', $string, $loginAppSecret, true);
        return (base64_encode($sign));
    }

}
