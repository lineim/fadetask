<?php

use Phpmig\Migration\Migration;

class AddUuidToKanban extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "ALTER TABLE `kanban` ADD COLUMN uuid char(36) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '看板唯一id' AFTER id";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = " ALTER TABLE `kanban` DROP COLUMN `uuid`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
