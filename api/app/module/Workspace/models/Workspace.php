<?php
namespace app\module\Workspace\models;

use support\Model;

class Workspace extends Model
{
    public const PAY_PLAN_FREE = 0;

    protected $table = 'workspace';

    public $timestamps = false;
}
