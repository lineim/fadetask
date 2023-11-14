<?php

return [
    'channel' => env('SMS_CHANNEL', 'aliyun'),
    'sign' => env('SMS_SIGIN', ''),
    'aliyun_sms_access_id' => env('ALIYUN_SMS_ACCESS_ID', ''),
    'aliyun_sms_access_secret' => env('ALIYUN_SMS_ACCESS_SECRET', ''),
    'aliyun_sms_endpoint' => env('ALIYUN_SMS_ENDPOINT', ''),
    'auth_tpl_code' => env('SMS_AUTH_TPL_CODE', '')
];
