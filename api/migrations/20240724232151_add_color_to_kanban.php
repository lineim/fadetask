<?php

use Phpmig\Migration\Migration;

class AddColorToKanban extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "alter table `kanban` add column `color` varchar(32) NOT NULL default 'blue' after `uuid`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = "alter table `kanban` drop column `color`";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
