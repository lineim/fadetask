<?php

use Phpmig\Migration\Migration;

class AddProjectIdToKanban extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "alter table `kanban` add column `project_id` int unsigned NOT NULL default 0 after `uuid`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = "alter table `kanban` drop column `project_id`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
