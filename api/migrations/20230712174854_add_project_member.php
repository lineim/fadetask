<?php

use Phpmig\Migration\Migration;

class AddProjectMember extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "CREATE TABLE `project_member` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `project_id` int unsigned NOT NULL,
            `user_id` int unsigned NOT NULL,
            `role` tinyint unsigned NOT NULL DEFAULT '3' COMMENT '角色: 0 Owner; 1 管理员; 2 项目经理; 3 产品; 4 研发; 5 测试;',
            `join_time` int unsigned NOT NULL,
            `is_delete` tinyint(1) NOT NULL DEFAULT '0',
            `created_time` int unsigned NOT NULL,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = "DROP TABLE IF EXISTS project_member;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
