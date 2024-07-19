<?php

use Phpmig\Migration\Migration;

class AddIsDeletedToWorkspace extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "alter table `workspace` add column `is_deleted` tinyint unsigned NOT NULL default 0 after `pay_plan`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = "alter table `workspace` drop column `is_deleted`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
