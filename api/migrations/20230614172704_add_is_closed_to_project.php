<?php

use Phpmig\Migration\Migration;

class AddIsClosedToProject extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "ALTER TABLE `project` ADD COLUMN `is_closed` TINYINT(1) unsigned NOT NULL DEFAULT 0 COMMENT '项目是否关闭' AFTER `user_id`";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = "ALTER TABLE `project` DROP COLUMN `is_closed`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
