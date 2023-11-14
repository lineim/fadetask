<?php
namespace app\model;

use support\Model;

class User extends Model
{

    const ROLE_ADMIN = 'ADMIN';
    const ROLE_USER  = 'USER';

    protected $table = 'user';

    public $timestamps = false;

    protected $fillable = ['uuid', 'email', 'passhash', 'name', 'mobile', 'hired_time', 'title', 'verified', 'avatar', 'role', 'created_time', 'updated_time'];

    public function bind()
    {
        return $this->hasOne('app\model\UserBind', 'user_id');
    }
    
}