<?php
namespace app\module\DataSync\Wework;

use app\module\DataSync\UserAdapter as BaseAdapter;

class UserAdapter extends BaseAdapter
{
    protected $type = 'wework';

    protected $userMap = [
        'name' => 'name',
        'mobile' => 'mobile',
        'thumb_avatar' => 'avatar',
        'position' => 'title',
        'email' => 'email',
        'status' => 'verified',
    ];

    protected $userBindMap = [
        'open_id' => 'out_user_id',
        'userid' => 'union_id',
    ];

    protected function verifiedFilter($v)
    {
        if ($v != 1) {
            return 0;
        }
        return $v;
    }
}
