<?php

use Phpmig\Migration\Migration;

class AddSortToLabel extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "ALTER TABLE kanban_label ADD COLUMN `sort` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序，数字越大越靠前' AFTER `kanban_id`";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = " ALTER TABLE `kanban_label` DROP COLUMN `sort`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
