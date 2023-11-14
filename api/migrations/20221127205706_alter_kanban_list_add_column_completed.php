<?php

use Phpmig\Migration\Migration;

class AlterKanbanListAddColumnCompleted extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $this->getContainer()['db']->exec("
            ALTER TABLE `kanban_list` ADD COLUMN `completed` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否为已完成列(1:是,0:否)' AFTER `archived`;
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->getContainer()['db']->exec("
            ALTER TABLE `kanban_list` DROP COLUMN `completed`;
        ");
    }
}
