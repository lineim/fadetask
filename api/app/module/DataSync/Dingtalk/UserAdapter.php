<?php
namespace app\module\DataSync\Dingtalk;

use app\module\DataSync\UserAdapter as BaseAdapter;
use app\model\User as UserModel;

class UserAdapter extends BaseAdapter
{

    protected $type = 'dingtalk';

    protected $userMap = [
        'name' => 'name',
        'mobile' => 'mobile',
        'avatar' => 'avatar',
        'title' => 'title',
        'email' => 'email',
        'active' => 'verified',
        'hired_date' => 'hired_time',
        'admin' => 'role',
    ];

    protected $userBindMap = [
        'userid' => 'out_user_id',
        'unionid' => 'union_id',
    ];

    protected function roleFilter($v)
    {
        if ($v) {
            return UserModel::ROLE_ADMIN;
        }
        return UserModel::ROLE_USER;
    }

    protected function hired_timeFilter($v)
    {
        return intval($v/1000);
    }

    protected function verifiedFilter($v)
    {
        return $v ? 1 : 0;
    }

}