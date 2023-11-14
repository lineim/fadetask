<?php
namespace app\model;

use support\Model;

class UserBind extends Model
{

    protected $table = 'user_bind';

    public $timestamps = false;

    protected $fillable = ['user_id', 'out_user_id', 'union_id', 'type', 'created_time'];
    
}