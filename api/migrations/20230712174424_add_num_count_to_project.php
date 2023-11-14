<?php

use Phpmig\Migration\Migration;

class AddNumCountToProject extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "ALTER TABLE `project` ADD COLUMN `kanban_num` INT unsigned NOT NULL DEFAULT 0 COMMENT '看板数' AFTER `user_id`, 
                ADD COLUMN `member_num` INT unsigned NOT NULL DEFAULT 0 COMMENT '成员数' AFTER `kanban_num`";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = "ALTER TABLE `project` DROP COLUMN `kanban_num`, DROP COLUMN `member_num`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
