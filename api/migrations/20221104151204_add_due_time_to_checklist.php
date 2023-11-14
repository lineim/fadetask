<?php

use Phpmig\Migration\Migration;

class AddDueTimeToChecklist extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "ALTER TABLE kanban_task_check_list ADD COLUMN due_time int(10) unsigned NOT NULL DEFAULT '0' COMMENT '到期时间' AFTER done_time;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = "DROP TABLE IF EXISTS kanban_task_check_list;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
