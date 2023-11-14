<?php

use Phpmig\Migration\Migration;

class AddCopyFromIdToTaskAttachment extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "ALTER TABLE `kanban_task_attachment` ADD COLUMN `copy_from_id` TINYINT unsigned NOT NULL DEFAULT '0' COMMENT '复制来源id' AFTER `task_id`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = " ALTER TABLE `kanban_task_attachment` DROP COLUMN `copy_from_id`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
