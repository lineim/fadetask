<?php
function smsCode($len = 6)
{
    $arr = [];
    for ($i = 0; $i < $len; $i ++) {
        $arr[] = mt_rand(0, 9);
    }
    return join($arr);
}
