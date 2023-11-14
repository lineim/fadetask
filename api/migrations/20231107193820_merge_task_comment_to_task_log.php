<?php

use Phpmig\Migration\Migration;

class MergeTaskCommentToTaskLog extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "alter table `kanban_task_log` 
            add column `parent_id` int unsigned NOT NULL DEFAULT 0 COMMENT 'parent id' AFTER `kanban_id`,
            add column `updated_time` int unsigned NOT NULL DEFAULT 0 COMMENT 'update time' AFTER `change`,
            add column `is_delete` tinyint unsigned NOT NULL DEFAULT '0'  AFTER `change`
        ";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = "alter table `kanban_task_log` drop column parent_id, created_time, is_delete";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
