<?php
namespace app\model;

use support\Model;

class ProjectMember extends Model
{
    // 角色: 0 Owner; 1 管理员; 2 项目经理; 3 产品; 4 研发; 5 测试;
    const ROLE_OWNER = 0;
    const ROLE_MANAGER = 1;
    const ROLE_PROJECT_MANAGER = 2;
    const ROLE_PRODUCT_MANAGER = 3;
    const ROLE_RD = 4;
    const ROLE_TEST = 5;

    protected $table = 'project_member';

    public $timestamps = false;

}
