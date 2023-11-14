<?php
namespace app\common\exception;

class BusinessException extends \Exception
{
    public function __construct($msg, $code = 700)
    {
        parent::__construct($msg, $code);
    }
}