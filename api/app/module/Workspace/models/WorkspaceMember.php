<?php
namespace app\module\Workspace\models;

use support\Model;

class WorkspaceMember extends Model
{

    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';

    protected $table = 'workspace_member';
    protected $connection = 'write';

    public $timestamps = false;

}
