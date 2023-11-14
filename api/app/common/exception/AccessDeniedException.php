<?php
namespace app\common\exception;

class AccessDeniedException extends \Exception
{
    const DEFAULT_MSG = 'access_denied';

    public function __construct($msg = 'access_denied', $code = 403)
    {
        parent::__construct($msg, $code);
    }
}