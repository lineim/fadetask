<?php

use Phpmig\Migration\Migration;

class AddUuidToKanbanTaskAttachment extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "ALTER TABLE `kanban_task_attachment` ADD COLUMN uuid CHAR(36) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '看板唯一id' AFTER id";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = " ALTER TABLE `kanban_task_attachment` DROP COLUMN `uuid`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
