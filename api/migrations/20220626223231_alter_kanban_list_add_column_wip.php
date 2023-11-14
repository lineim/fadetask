<?php

use Phpmig\Migration\Migration;

class AlterKanbanListAddColumnWip extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "alter table `kanban_list` add column `wip` tinyint unsigned NOT NULL DEFAULT 0 COMMENT 'WIP限制，限制卡片数量，0为不限制' AFTER `task_count`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec("alter table `kanban_list` drop column `wip`");
    }
}
