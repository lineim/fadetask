<?php
namespace app\common\exception;

class InvalidParamsException extends \Exception
{

    public function __construct($msg, $code = 600)
    {
        parent::__construct($msg, $code);
    }

}