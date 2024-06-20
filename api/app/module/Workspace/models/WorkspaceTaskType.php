<?php
namespace app\module\Workspace\models;

use support\Model;

class WorkspaceTaskType extends Model
{

    protected $table = 'workspace_task_type';
    protected $connection = 'write';

    public $timestamps = false;
    
}