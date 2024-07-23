<?php

use Phpmig\Migration\Migration;

class AddColorToProject extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "alter table `project` add column `color` varchar(32) NOT NULL default 'blue' after `is_public`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = "alter table `project` drop column `color`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
