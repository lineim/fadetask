<?php

use Phpmig\Migration\Migration;

class AddSubtaskCountToTask extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "alter table kanban_task add column `subtask_count` int unsigned NOT NULL default 0 after `parent_id`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = "alter table kanban_task drop column `subtask_count`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
