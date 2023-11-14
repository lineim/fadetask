<?php
namespace app\common\exception;

/**
 * 调用第三方接口异常类.
 */
class ThirdPartException extends \Exception
{
    public function __construct($msg, $code = 800)
    {
        parent::__construct($msg, $code);
    }
}