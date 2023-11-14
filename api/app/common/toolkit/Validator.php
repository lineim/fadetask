<?php
namespace app\common\toolkit;

use phpDocumentor\Reflection\Types\Boolean;

class Validator
{

    const MIN_PASSWORD = 6;

    public static function password($password)
    {
        return is_string($password) && mb_strlen($password) >= self::MIN_PASSWORD;
    }

    public static function mobile($mobile)
    {
        return preg_match('/^1\d{10}$/', $mobile) > 0;
    }

    public static function email($email)
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

}