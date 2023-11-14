<?php
namespace app\common\mailer\Aliyun;

class Sign 
{
    protected $httpMethod = 'POST';

    public function __construct()
    {
        
    }

    public function sign(array $params)
    {

    }

    protected function formatParams(array $params)
    {
        
    }

    protected function setHttpMethod($method)
    {
        $this->httpMethod = $method;
    }

}
