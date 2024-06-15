<?php

use Phpmig\Migration\Migration;

class AddParentIdToTask extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "alter table kanban_task add column `parent_id` int unsigned NOT NULL default 0 after `id`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = "alter table kanban_task drop column `parent_id`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
