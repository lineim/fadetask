<?php
/**
 * This file is part of fade task project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\common\oss\Aliyun;

use app\common\oss\OssInterface;
use AlibabaCloud\SDK\Sts\V20150401\Sts;
use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\Sts\V20150401\Models\AssumeRoleRequest;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions;
use Monolog\Logger;
use OSS\OssClient;
use OSS\Core\OssException;

class AliyunOss implements OssInterface
{
    private $logger;

    private $accessKeyId;
    private $accessKeySecret;
    private $roleArn = 'acs:ram::1832384413262110:role/ramoss';
    private $endpoint = 'oss-cn-chengdu.aliyuncs.com';
    private $stsEndpoint = 'sts.cn-hangzhou.aliyuncs.com';
    private $bucket;
    private $stsClient;

    private static $instance;

    private function __construct($accessKeyId, $accessKeySecret, $bucket, $endpoint = '', $stsEndpoint = '')
    {   
        $this->accessKeyId = $accessKeyId;
        $this->accessKeySecret = $accessKeySecret;
        $this->bucket = $bucket;
        if ($endpoint) {
            $this->endpoint = $endpoint;
        }
        if ($stsEndpoint) {
            $this->stsEndpoint = $stsEndpoint;
        }
        $this->init();
    }

    public static function instance($accessKeyId, $accessKeySecret, $bucket, $endpoint = '') : OssInterface
    {
        if (!self::$instance) {
            self::$instance = new self($accessKeyId, $accessKeySecret, $bucket, $endpoint);
        }
        return self::$instance;
    }

    private function init()
    {
        $config = new Config([        
            "accessKeyId" => $this->accessKeyId,        
            "accessKeySecret" => $this->accessKeySecret,
        ]);
        $config->endpoint = $this->stsEndpoint;
        $client =  new Sts($config); 
        $this->setStsClient($client);
    }

    public function getWriteKeys(int $expire) : array
    {
       return $this->getKeys($expire, 'oss_write_session');
    }

    public function getReadKeys(int $expire) : array
    {
        return $this->getKeys($expire, 'oss_read_session');
    }

    public function getUploadUrl(string $object, int $timeout = 3600) : string
    {
        $keys = $this->getWriteKeys(3600);
        $accessKeyId = $keys['accessKeyId'];
        $accessKeySecret = $keys['accessKeySecret'];
        $securityToken = $keys['securityToken'];

        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $this->endpoint, false, $securityToken);
            return $ossClient->signUrl($this->bucket, $object, $timeout, "PUT");
        } catch (OssException $e) {
            $this->logException($e);
            throw $e;
        }
    }

    public function getDownloadUrl(string $object, int $timeout = 3600) : string
    {
        $keys = $this->getReadKeys(3600);
        $accessKeyId = $keys['accessKeyId'];
        $accessKeySecret = $keys['accessKeySecret'];
        $securityToken = $keys['securityToken'];

        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $this->endpoint, false, $securityToken);
            return $ossClient->signUrl($this->bucket, $object, $timeout, "GET");
        } catch (OssException $e) {
            $this->logException($e);
            throw $e;
        }
    }

    public function setRoleArn($roleArn)
    {
        $this->roleArn = $roleArn;
    }

    public function setStsClient(Sts $stsClient) : void
    {
        $this->stsClient = $stsClient;
    }

    public function setLogger(Logger $logger) : void
    {
        $this->logger = $logger;
    }

    public function setBucket(string $bucket): void
    {
        $this->bucket = $bucket;
    }

    private function getKeys($expire, $sessionName, array $policy = [])
    {
        $params = [
            "roleArn" => $this->roleArn,
            "roleSessionName" => $sessionName,
            "durationSeconds" => $expire < 900 ? 900 : $expire,
        ];
        if (is_array($policy) && !empty($policy)) {
            $params['policy'] = json_encode($policy);
        }
        $assumeRoleRequest = new AssumeRoleRequest($params);

        $runtime = new RuntimeOptions([
            'connectTimeout' => 5000,
            'readTimeout' => 5000,
        ]);
        try {
            $result = $this->stsClient->assumeRoleWithOptions($assumeRoleRequest, $runtime);
            return [
                'accessKeyId' => $result->body->credentials->accessKeyId,
                'accessKeySecret' => $result->body->credentials->accessKeySecret,
                'expiration' => $result->body->credentials->expiration,
                'securityToken' => $result->body->credentials->securityToken
            ];
        } catch(\Exception $e) {
            $this->logException($e);
            throw $e;
        }
    }

    private function logException(\Exception $e) 
    {
        if ($this->logger instanceof Logger) {
            $this->logger->error($e->getMessage());
        }
    }

}
