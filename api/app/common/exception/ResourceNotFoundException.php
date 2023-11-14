<?php
namespace app\common\exception;

class ResourceNotFoundException extends \Exception
{
    public function __construct($msg, $code = 404)
    {
        parent::__construct($msg, $code);
    }
}
